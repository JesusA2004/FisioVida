<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MiJornadaController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $today  = Carbon::today();

        $appointments = DB::table('appointments as a')
            ->join('personas as p', 'p.id', '=', 'a.patient_persona_id')
            ->join('users as u', 'u.id', '=', 'a.therapist_user_id')
            ->whereNull('p.deleted_at')
            ->where('a.therapist_user_id', $userId)
            ->whereDate('a.start_at', $today)
            ->orderBy('a.start_at')
            ->select([
                'a.id',
                'a.patient_persona_id',
                'a.start_at',
                'a.end_at',
                'a.status',
                'a.notes',
                'u.name as therapist_name',
                DB::raw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) as patient_name"),
            ])
            ->get()
            ->map(function ($appt) {
                $patientId = $appt->patient_persona_id;

                // Última sesión del paciente
                $lastSession = DB::table('therapy_sessions')
                    ->where('patient_persona_id', $patientId)
                    ->orderByDesc('session_date')
                    ->first(['session_date', 'pain_scale', 'assessment', 'plan', 'subjective', 'notes']);

                return [
                    'id'                 => $appt->id,
                    'patient_persona_id' => $patientId,
                    'patient_name'       => $appt->patient_name ?: 'Sin nombre',
                    'therapist_name'     => $appt->therapist_name ?? '',
                    'start_at'           => $appt->start_at,
                    'end_at'             => $appt->end_at,
                    'status'             => $appt->status,
                    'notes'              => $appt->notes,
                    'last_session'       => $lastSession ? [
                        'session_date' => $lastSession->session_date,
                        'pain_scale'   => $lastSession->pain_scale,
                        'assessment'   => $lastSession->assessment,
                        'plan'         => $lastSession->plan,
                        'subjective'   => $lastSession->subjective,
                        'notes'        => $lastSession->notes,
                    ] : null,
                ];
            });

        return Inertia::render('MiJornada/Index', [
            'appointments' => $appointments,
            'today'        => $today->format('Y-m-d'),
            'todayLabel'   => now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY'),
        ]);
    }

    // GET /mi-jornada/citas/{cita}/atencion
    // Devuelve snapshot completo del paciente para el panel de atención inline
    public function atencion(Request $request, int $cita)
    {
        $userId = auth()->id();

        $appointment = DB::table('appointments as a')
            ->join('personas as p', 'p.id', '=', 'a.patient_persona_id')
            ->where('a.id', $cita)
            ->where('a.therapist_user_id', $userId)
            ->whereNull('p.deleted_at')
            ->select([
                'a.id', 'a.status', 'a.start_at', 'a.end_at', 'a.notes',
                'a.patient_persona_id',
                DB::raw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) as patient_name"),
                'p.fecha_nacimiento', 'p.sexo', 'p.telefono',
                'p.notas as patient_notes',
                ...( Schema::hasColumn('personas', 'email') ? ['p.email'] : []),
            ])
            ->first();

        abort_if(! $appointment, 404);

        $patientId = $appointment->patient_persona_id;

        // Últimas 5 sesiones
        $sessions = DB::table('therapy_sessions as s')
            ->leftJoin('users as u', 'u.id', '=', 's.therapist_user_id')
            ->where('s.patient_persona_id', $patientId)
            ->orderByDesc('s.session_date')
            ->limit(5)
            ->get(['s.id', 's.session_date', 's.pain_scale', 's.assessment', 's.plan', 's.subjective', 's.notes', 'u.name as therapist_name']);

        $sessionIds = $sessions->pluck('id');

        // Ejercicios de las últimas sesiones
        $exercises = $sessionIds->isNotEmpty()
            ? DB::table('session_exercises as se')
                ->join('exercises as e', 'e.id', '=', 'se.exercise_id')
                ->whereIn('se.session_id', $sessionIds)
                ->orderByDesc('se.session_id')
                ->limit(20)
                ->get(['se.session_id', 'se.sets', 'se.reps', 'se.seconds', 'e.name', 'e.description', 'e.video_url'])
            : collect();

        // Archivos recientes
        $files = DB::table('files as f')
            ->leftJoin('therapy_sessions as s', 's.id', '=', 'f.session_id')
            ->where(function ($w) use ($patientId) {
                $w->where('f.patient_persona_id', $patientId)
                  ->orWhere('s.patient_persona_id', $patientId);
            })
            ->whereNull('f.deleted_at')
            ->orderByDesc('f.id')
            ->limit(10)
            ->get(['f.id', 'f.original_name', 'f.file_type', 'f.created_at'])
            ->map(fn ($f) => [
                'id'            => $f->id,
                'original_name' => $f->original_name,
                'file_type'     => $f->file_type,
                'created_at'    => $f->created_at,
                'download_url'  => route('archivos.download', $f->id),
            ])->values();

        // Próximas citas del paciente (además de la actual)
        $upcoming = DB::table('appointments as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.therapist_user_id')
            ->where('a.patient_persona_id', $patientId)
            ->where('a.id', '!=', $cita)
            ->whereIn('a.status', ['scheduled', 'confirmed'])
            ->where('a.start_at', '>=', now())
            ->orderBy('a.start_at')
            ->limit(3)
            ->get(['a.id', 'a.status', 'a.start_at', 'u.name as therapist_name']);

        // Actividades pendientes
        $activities = DB::table('activities')
            ->where('patient_persona_id', $patientId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('due_date')
            ->limit(5)
            ->get(['id', 'title', 'status', 'priority', 'due_date']);

        // Catálogo de ejercicios activos para asignar
        $exerciseCatalog = DB::table('exercises')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->limit(60)
            ->get(['id', 'name', 'description', 'video_url']);

        // Compliance warnings para mostrar antes del formulario SOAP
        $complianceWarnings = $this->complianceWarnings((int) $patientId);

        return response()->json([
            'appointment'        => $appointment,
            'sessions'           => $sessions,
            'exercises'          => $exercises,
            'files'              => $files,
            'upcoming'           => $upcoming,
            'activities'         => $activities,
            'exerciseCatalog'    => $exerciseCatalog,
            'complianceWarnings' => $complianceWarnings,
        ]);
    }

    private function complianceWarnings(int $patientId): array
    {
        if (! Schema::hasTable('patient_legal_acceptances') || ! Schema::hasTable('system_settings')) {
            return [];
        }

        $settings = DB::table('system_settings')
            ->whereIn('key', [
                'require_privacy_notice_before_session',
                'require_treatment_consent_before_session',
            ])
            ->pluck('value', 'key');

        $warnings = [];

        if (($settings['require_privacy_notice_before_session'] ?? '0') === '1') {
            $has = DB::table('patient_legal_acceptances')
                ->where('patient_persona_id', $patientId)
                ->where('document_type', 'privacy_notice')
                ->where('status', 'accepted')
                ->exists();

            if (! $has) {
                $warnings[] = 'Falta aviso de privacidad aceptado (requerido para sesiones).';
            }
        }

        if (($settings['require_treatment_consent_before_session'] ?? '0') === '1') {
            $has = DB::table('patient_legal_acceptances')
                ->where('patient_persona_id', $patientId)
                ->where('document_type', 'treatment_consent')
                ->where('status', 'accepted')
                ->exists()
                || DB::table('patient_consents')
                    ->where('patient_persona_id', $patientId)
                    ->where('consent_type', 'tratamiento')
                    ->where('status', 'active')
                    ->exists();

            if (! $has) {
                $warnings[] = 'Falta consentimiento de tratamiento (requerido para sesiones).';
            }
        }

        return $warnings;
    }

    // POST /mi-jornada/citas/{cita}/sesion
    public function registrarSesion(Request $request, int $cita)
    {
        $userId = auth()->id();

        $appointment = DB::table('appointments')
            ->where('id', $cita)
            ->where('therapist_user_id', $userId)
            ->first();

        abort_if(! $appointment, 403);

        // Bloqueo de cumplimiento
        $warnings = $this->complianceWarnings((int) $appointment->patient_persona_id);
        if (! empty($warnings)) {
            return response()->json([
                'ok'      => false,
                'blocked' => true,
                'message' => implode(' / ', $warnings),
            ], 422);
        }

        $data = $request->validate([
            'subjective'   => 'nullable|string|max:2000',
            'objective'    => 'nullable|string|max:2000',
            'assessment'   => 'nullable|string|max:2000',
            'plan'         => 'nullable|string|max:2000',
            'pain_scale'   => 'nullable|integer|min:0|max:10',
            'notes'        => 'nullable|string|max:2000',
            'exercise_ids' => 'nullable|array',
            'exercise_ids.*' => 'integer|exists:exercises,id',
            'marcar_done'  => 'boolean',
        ]);

        $sessionId = DB::table('therapy_sessions')->insertGetId([
            'appointment_id'    => $cita,
            'patient_persona_id'=> $appointment->patient_persona_id,
            'therapist_user_id' => $userId,
            'session_date'      => Carbon::today()->toDateString(),
            'subjective'        => $data['subjective'] ?? null,
            'objective'         => $data['objective'] ?? null,
            'assessment'        => $data['assessment'] ?? null,
            'plan'              => $data['plan'] ?? null,
            'pain_scale'        => $data['pain_scale'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        if (! empty($data['exercise_ids'])) {
            $rows = array_map(fn ($eid) => [
                'session_id'  => $sessionId,
                'exercise_id' => $eid,
                'sets'        => 3,
                'reps'        => 10,
                'seconds'     => 0,
                'notes'       => null,
            ], $data['exercise_ids']);
            DB::table('session_exercises')->insert($rows);
        }

        if (! empty($data['marcar_done']) && $appointment->status === 'arrived') {
            DB::table('appointments')
                ->where('id', $cita)
                ->update(['status' => 'done', 'updated_at' => now()]);
        }

        return response()->json(['session_id' => $sessionId, 'ok' => true]);
    }
}
