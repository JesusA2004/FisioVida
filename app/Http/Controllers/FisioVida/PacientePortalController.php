<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class PacientePortalController extends Controller
{
    public function index(Request $request)
    {
        $user       = $request->user();
        $personaId  = $user->persona_id;

        // Sin vínculo a expediente → pantalla amigable
        if (! $personaId) {
            $clinicSettings = $this->clinicSettings();
            return Inertia::render('PacientePortal/Index', [
                'unlinked'      => true,
                'patient'       => null,
                'appointments'  => [],
                'sessions'      => [],
                'exercises'     => [],
                'files'         => [],
                'activities'    => [],
                'consents'      => [],
                'clinicSettings' => $clinicSettings,
            ]);
        }

        $hasEmail = Schema::hasColumn('personas', 'email');

        $patient = DB::table('personas')
            ->where('id', $personaId)
            ->whereNull('deleted_at')
            ->select(array_filter([
                'id', 'nombres', 'apellido_paterno', 'apellido_materno',
                'fecha_nacimiento', 'sexo', 'telefono', 'direccion', 'notas',
                $hasEmail ? 'email' : null,
            ]))
            ->first();

        // Seguridad: el persona_id del usuario debe existir en personas
        abort_if(! $patient, 404);

        // Próximas citas
        $upcomingAppointments = DB::table('appointments as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.therapist_user_id')
            ->where('a.patient_persona_id', $personaId)
            ->whereIn('a.status', ['scheduled', 'confirmed'])
            ->where('a.start_at', '>=', now())
            ->orderBy('a.start_at')
            ->limit(5)
            ->get(['a.id', 'a.status', 'a.start_at', 'a.end_at', 'u.name as therapist_name']);

        // Últimas citas
        $recentAppointments = DB::table('appointments as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.therapist_user_id')
            ->where('a.patient_persona_id', $personaId)
            ->orderByDesc('a.start_at')
            ->limit(10)
            ->get(['a.id', 'a.status', 'a.start_at', 'a.end_at', 'u.name as therapist_name']);

        // Últimas sesiones con historial de dolor
        $sessions = DB::table('therapy_sessions as s')
            ->leftJoin('users as u', 'u.id', '=', 's.therapist_user_id')
            ->where('s.patient_persona_id', $personaId)
            ->orderByDesc('s.session_date')
            ->limit(15)
            ->get([
                's.id', 's.session_date', 's.pain_scale',
                's.assessment', 's.plan', 's.subjective', 's.notes',
                'u.name as therapist_name',
            ]);

        // Ejercicios asignados (de todas las sesiones, únicos por ejercicio)
        $sessionIds = $sessions->pluck('id');
        $exercises  = [];
        if ($sessionIds->isNotEmpty()) {
            $exercises = DB::table('session_exercises as se')
                ->join('exercises as e', 'e.id', '=', 'se.exercise_id')
                ->whereIn('se.session_id', $sessionIds)
                ->orderByDesc('se.session_id')
                ->get([
                    'se.session_id', 'se.sets', 'se.reps', 'se.seconds', 'se.notes as exercise_notes',
                    'e.id as exercise_id', 'e.name', 'e.description', 'e.video_url',
                ])
                ->toArray();
        }

        // Archivos compartidos
        $files = DB::table('files as f')
            ->leftJoin('therapy_sessions as s', 's.id', '=', 'f.session_id')
            ->leftJoin('users as u', 'u.id', '=', 'f.uploaded_by')
            ->where(function ($where) use ($personaId) {
                $where->where('f.patient_persona_id', $personaId)
                    ->orWhere('s.patient_persona_id', $personaId);
            })
            ->whereNull('f.deleted_at')
            ->orderByDesc('f.id')
            ->limit(20)
            ->get([
                'f.id', 'f.original_name', 'f.file_type', 'f.created_at',
                'u.name as uploaded_by_name',
            ])
            ->map(fn ($file) => [
                'id'               => $file->id,
                'original_name'    => $file->original_name,
                'file_type'        => $file->file_type,
                'created_at'       => $file->created_at,
                'uploaded_by_name' => $file->uploaded_by_name,
                'preview_url'      => route('archivos.show', $file->id),
                'download_url'     => route('archivos.download', $file->id),
            ])
            ->values();

        // Actividades pendientes asociadas al paciente
        $activities = DB::table('activities as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.responsible_user_id')
            ->where('a.patient_persona_id', $personaId)
            ->whereIn('a.status', ['pending', 'in_progress'])
            ->orderBy('a.due_date')
            ->limit(10)
            ->get(['a.id', 'a.title', 'a.status', 'a.priority', 'a.due_date']);

        // Consentimientos
        $consents = Schema::hasTable('patient_consents')
            ? DB::table('patient_consents')
                ->where('patient_persona_id', $personaId)
                ->orderByDesc('accepted_at')
                ->get(['id', 'consent_type', 'accepted_at'])
                ->toArray()
            : [];

        $clinicSettings = $this->clinicSettings();

        return Inertia::render('PacientePortal/Index', [
            'unlinked'            => false,
            'patient'             => [
                'id'             => $patient->id,
                'full_name'      => trim(($patient->nombres ?? '').' '.($patient->apellido_paterno ?? '').' '.($patient->apellido_materno ?? '')),
                'nombres'        => $patient->nombres,
                'fecha_nacimiento' => $patient->fecha_nacimiento,
                'sexo'           => $patient->sexo,
                'telefono'       => $patient->telefono,
                'email'          => $patient->email ?? null,
                'direccion'      => $patient->direccion,
                'notas'          => $patient->notas,
            ],
            'upcomingAppointments' => $upcomingAppointments,
            'recentAppointments'   => $recentAppointments,
            'sessions'             => $sessions,
            'exercises'            => $exercises,
            'files'                => $files,
            'activities'           => $activities,
            'consents'             => $consents,
            'clinicSettings'       => $clinicSettings,
        ]);
    }

    private function clinicSettings(): array
    {
        if (! Schema::hasTable('system_settings')) {
            return [];
        }

        return DB::table('system_settings')
            ->whereIn('key', ['clinic_name', 'clinic_phone', 'clinic_email', 'clinic_address', 'clinic_logo'])
            ->pluck('value', 'key')
            ->toArray();
    }
}
