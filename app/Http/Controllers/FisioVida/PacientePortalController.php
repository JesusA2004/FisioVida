<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PacientePortalController extends Controller
{
    public function index(Request $request)
    {
        $user      = $request->user();
        $personaId = $user->persona_id;

        if (! $personaId) {
            return Inertia::render('PacientePortal/Index', [
                'unlinked'       => true,
                'patient'        => null,
                'stats'          => $this->emptyStats(),
                'upcomingAppointments' => [],
                'recentAppointments'   => [],
                'sessions'       => [],
                'painHistory'    => [],
                'exercises'      => [],
                'files'          => [],
                'activities'     => [],
                'consents'       => [],
                'myRequests'     => [],
                'clinicSettings' => $this->clinicSettings(),
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

        abort_if(! $patient, 404);

        // ---------- Citas próximas ----------
        $upcomingAppointments = DB::table('appointments as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.therapist_user_id')
            ->where('a.patient_persona_id', $personaId)
            ->whereIn('a.status', ['scheduled', 'confirmed'])
            ->where('a.start_at', '>=', now())
            ->orderBy('a.start_at')
            ->limit(5)
            ->get(['a.id', 'a.status', 'a.start_at', 'a.end_at', 'u.name as therapist_name']);

        // ---------- Historial de citas ----------
        $recentAppointments = DB::table('appointments as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.therapist_user_id')
            ->where('a.patient_persona_id', $personaId)
            ->orderByDesc('a.start_at')
            ->limit(12)
            ->get(['a.id', 'a.status', 'a.start_at', 'a.end_at', 'u.name as therapist_name']);

        // ---------- Sesiones con SOAP simplificado ----------
        $sessions = DB::table('therapy_sessions as s')
            ->leftJoin('users as u', 'u.id', '=', 's.therapist_user_id')
            ->where('s.patient_persona_id', $personaId)
            ->orderByDesc('s.session_date')
            ->limit(20)
            ->get([
                's.id', 's.session_date', 's.pain_scale',
                's.plan', 's.subjective', 's.notes',
                'u.name as therapist_name',
            ]);

        // ---------- Historial de dolor para gráfica ----------
        $painHistory = DB::table('therapy_sessions')
            ->where('patient_persona_id', $personaId)
            ->whereNotNull('pain_scale')
            ->orderBy('session_date')
            ->limit(30)
            ->get(['session_date', 'pain_scale'])
            ->map(fn ($r) => ['date' => $r->session_date, 'value' => (int) $r->pain_scale]);

        // ---------- Ejercicios asignados (últimas sesiones, únicos por ejercicio) ----------
        $sessionIds = $sessions->pluck('id');
        $exercises  = [];
        if ($sessionIds->isNotEmpty()) {
            $exercises = DB::table('session_exercises as se')
                ->join('exercises as e', 'e.id', '=', 'se.exercise_id')
                ->whereIn('se.session_id', $sessionIds)
                ->orderByDesc('se.session_id')
                ->get([
                    'se.session_id', 'se.sets', 'se.reps', 'se.seconds',
                    'se.notes as exercise_notes',
                    'e.id as exercise_id', 'e.name', 'e.description', 'e.video_url',
                ])
                ->unique('exercise_id')
                ->values()
                ->toArray();
        }

        // ---------- Archivos visibles para paciente ----------
        $hasVisibleCol = Schema::hasColumn('files', 'visible_to_patient');

        $filesQuery = DB::table('files as f')
            ->leftJoin('therapy_sessions as s', 's.id', '=', 'f.session_id')
            ->leftJoin('users as u', 'u.id', '=', 'f.uploaded_by')
            ->where(function ($where) use ($personaId) {
                $where->where('f.patient_persona_id', $personaId)
                      ->orWhere('s.patient_persona_id', $personaId);
            })
            ->whereNull('f.deleted_at');

        if ($hasVisibleCol) {
            $filesQuery->where('f.visible_to_patient', true);
        }

        $files = $filesQuery
            ->orderByDesc('f.id')
            ->limit(20)
            ->get([
                'f.id', 'f.original_name', 'f.file_type', 'f.mime',
                'f.created_at', 'f.size_bytes',
                $hasVisibleCol ? 'f.visible_to_patient' : DB::raw('1 as visible_to_patient'),
                'u.name as uploaded_by_name',
            ])
            ->map(fn ($file) => [
                'id'               => $file->id,
                'original_name'    => $file->original_name,
                'file_type'        => $file->file_type,
                'mime'             => $file->mime,
                'size_bytes'       => $file->size_bytes,
                'created_at'       => $file->created_at,
                'uploaded_by_name' => $file->uploaded_by_name,
                'preview_url'      => route('paciente.archivos.ver', $file->id),
                'download_url'     => route('paciente.archivos.descargar', $file->id),
                'is_image'         => str_starts_with($file->mime ?? '', 'image/'),
            ])
            ->values();

        // ---------- Actividades / indicaciones pendientes ----------
        $activities = DB::table('activities as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.responsible_user_id')
            ->where('a.patient_persona_id', $personaId)
            ->whereIn('a.status', ['pending', 'in_progress'])
            ->orderBy('a.due_date')
            ->limit(10)
            ->get(['a.id', 'a.title', 'a.status', 'a.priority', 'a.due_date']);

        // ---------- Consentimientos (tabla legacy) ----------
        $consents = Schema::hasTable('patient_consents')
            ? DB::table('patient_consents')
                ->where('patient_persona_id', $personaId)
                ->orderByDesc('accepted_at')
                ->get(['id', 'consent_type', 'accepted_at'])
                ->toArray()
            : [];

        // ---------- Aceptaciones legales (nueva tabla unificada) ----------
        $legalAcceptances = Schema::hasTable('patient_legal_acceptances')
            ? DB::table('patient_legal_acceptances')
                ->where('patient_persona_id', $personaId)
                ->orderByDesc('accepted_at')
                ->get([
                    'id', 'document_type', 'version', 'title',
                    'accepted_at', 'status', 'source',
                    'signer_name', 'signature_method', 'signed_pdf_path', 'signed_at',
                ])
                ->toArray()
            : [];

        // ---------- Configuración de compliance (para mostrar textos en portal) ----------
        $complianceSettings = [];
        if (Schema::hasTable('system_settings')) {
            $complianceSettings = DB::table('system_settings')
                ->whereIn('key', [
                    'privacy_notice_version', 'privacy_notice_effective_date',
                    'privacy_notice_text', 'sensitive_data_consent_text',
                    'treatment_consent_text', 'image_consent_text', 'minor_consent_text',
                    'allow_patient_portal_acceptance',
                    'privacy_responsible_name', 'privacy_contact_email',
                ])
                ->pluck('value', 'key')
                ->toArray();
        }

        // ---------- Aviso de privacidad (tabla legacy) ----------
        $privacyNotices = Schema::hasTable('privacy_notice_acceptances')
            ? DB::table('privacy_notice_acceptances')
                ->where('patient_persona_id', $personaId)
                ->orderByDesc('accepted_at')
                ->limit(1)
                ->get(['id', 'version', 'accepted_at'])
                ->toArray()
            : [];

        // ---------- Mis solicitudes de cita ----------
        $myRequests = [];
        if (Schema::hasTable('appointment_requests')) {
            $myRequests = DB::table('appointment_requests as ar')
                ->leftJoin('users as rev', 'rev.id', '=', 'ar.reviewed_by')
                ->where('ar.patient_persona_id', $personaId)
                ->orderByDesc('ar.created_at')
                ->limit(10)
                ->get([
                    'ar.id', 'ar.preferred_date', 'ar.preferred_time', 'ar.reason',
                    'ar.status', 'ar.rejection_reason', 'ar.reviewed_at', 'ar.created_at',
                    'rev.name as reviewed_by_name',
                ])
                ->toArray();
        }

        // ---------- Stats de resumen ----------
        $totalSessions  = DB::table('therapy_sessions')->where('patient_persona_id', $personaId)->count();
        $totalAppts     = DB::table('appointments')->where('patient_persona_id', $personaId)->count();
        $lastPainScale  = DB::table('therapy_sessions')
            ->where('patient_persona_id', $personaId)
            ->whereNotNull('pain_scale')
            ->orderByDesc('session_date')
            ->value('pain_scale');
        $nextAppt = $upcomingAppointments->first();

        $stats = [
            'total_sessions'   => $totalSessions,
            'total_appts'      => $totalAppts,
            'active_exercises' => count($exercises),
            'last_pain_scale'  => $lastPainScale !== null ? (int) $lastPainScale : null,
            'next_appointment' => $nextAppt ? ['start_at' => $nextAppt->start_at, 'therapist_name' => $nextAppt->therapist_name] : null,
            'pending_requests' => collect($myRequests)->where('status', 'pending')->count(),
        ];

        return Inertia::render('PacientePortal/Index', [
            'unlinked'             => false,
            'patient'              => [
                'id'              => $patient->id,
                'full_name'       => trim(($patient->nombres ?? '').' '.($patient->apellido_paterno ?? '').' '.($patient->apellido_materno ?? '')),
                'nombres'         => $patient->nombres,
                'fecha_nacimiento'=> $patient->fecha_nacimiento,
                'sexo'            => $patient->sexo,
                'telefono'        => $patient->telefono,
                'email'           => $patient->email ?? null,
                'direccion'       => $patient->direccion,
                'notas'           => $patient->notas,
            ],
            'stats'                => $stats,
            'upcomingAppointments' => $upcomingAppointments,
            'recentAppointments'   => $recentAppointments,
            'sessions'             => $sessions,
            'painHistory'          => $painHistory,
            'exercises'            => $exercises,
            'files'                => $files,
            'activities'           => $activities,
            'consents'             => $consents,
            'legalAcceptances'     => $legalAcceptances,
            'privacyNotices'       => $privacyNotices,
            'complianceSettings'   => $complianceSettings,
            'myRequests'           => $myRequests,
            'clinicSettings'       => $this->clinicSettings(),
        ]);
    }

    public function verArchivo(Request $request, int $archivo)
    {
        $file = $this->resolvePatientOwnedFile($request, $archivo);
        $disk = $file->disk ?? 'private';

        abort_if(! Storage::disk($disk)->exists($file->path), 404);

        return response()->file(
            Storage::disk($disk)->path($file->path),
            [
                'Content-Type'        => $file->mime ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . addslashes($file->original_name ?? 'archivo') . '"',
            ],
        );
    }

    public function descargarArchivo(Request $request, int $archivo)
    {
        $file = $this->resolvePatientOwnedFile($request, $archivo);
        $disk = $file->disk ?? 'private';

        abort_if(! Storage::disk($disk)->exists($file->path), 404);

        return Storage::disk($disk)->download(
            $file->path,
            $file->original_name ?: basename($file->path),
        );
    }

    private function resolvePatientOwnedFile(Request $request, int $fileId): object
    {
        $personaId = $request->user()->persona_id;
        abort_if(! $personaId, 403);

        $file = DB::table('files')
            ->where('id', $fileId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(! $file, 404);

        // Require visible_to_patient if column exists
        if (Schema::hasColumn('files', 'visible_to_patient') && ! $file->visible_to_patient) {
            abort(403);
        }

        // Validate ownership: direct attachment or via session
        $ownedDirect  = $file->patient_persona_id === $personaId;
        $ownedViaSession = false;
        if (! $ownedDirect && $file->session_id) {
            $ownedViaSession = DB::table('therapy_sessions')
                ->where('id', $file->session_id)
                ->where('patient_persona_id', $personaId)
                ->exists();
        }

        abort_if(! $ownedDirect && ! $ownedViaSession, 403);

        return $file;
    }

    private function emptyStats(): array
    {
        return [
            'total_sessions'   => 0,
            'total_appts'      => 0,
            'active_exercises' => 0,
            'last_pain_scale'  => null,
            'next_appointment' => null,
            'pending_requests' => 0,
        ];
    }

    private function clinicSettings(): array
    {
        if (! Schema::hasTable('system_settings')) {
            return [];
        }

        return DB::table('system_settings')
            ->whereIn('key', [
                'clinic_name', 'clinic_phone', 'clinic_email', 'clinic_address', 'clinic_logo',
                'privacy_responsible_name', 'privacy_contact_email', 'privacy_notice_version',
            ])
            ->pluck('value', 'key')
            ->toArray();
    }
}
