<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Mail\AppointmentRequestApprovedMail;
use App\Mail\AppointmentRequestReceivedMail;
use App\Mail\AppointmentRequestRejectedMail;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Throwable;

class AppointmentRequestsController extends Controller
{
    // GET /solicitudes-cita   (admin/recepcion/terapeuta)
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();
        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 20, 50], true) ? $perPage : 15;

        $query = DB::table('appointment_requests as ar')
            ->join('personas as p', 'p.id', '=', 'ar.patient_persona_id')
            ->leftJoin('users as rev', 'rev.id', '=', 'ar.reviewed_by')
            ->leftJoin('users as cr', 'cr.id', '=', 'ar.created_by')
            ->whereNull('p.deleted_at')
            ->select([
                'ar.id', 'ar.patient_persona_id', 'ar.preferred_date', 'ar.preferred_time',
                'ar.reason', 'ar.notes', 'ar.status', 'ar.approved_appointment_id',
                'ar.rejection_reason', 'ar.reviewed_at', 'ar.created_at',
                DB::raw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) as patient_name"),
                'p.telefono as patient_phone',
                'p.email as patient_email',
                'rev.name as reviewed_by_name',
            ])
            ->orderByDesc('ar.created_at');

        if ($status !== '') {
            $query->where('ar.status', $status);
        }

        $search = $request->string('search')->toString();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) LIKE ?", ["%$search%"])
                  ->orWhere('ar.reason', 'like', "%$search%");
            });
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return Inertia::render('SolicitudesCita/Index', [
            'rows'    => $paginator,
            'filters' => ['status' => $status, 'search' => $search, 'per_page' => $perPage],
        ]);
    }

    // POST /solicitudes-cita  (paciente)
    public function store(Request $request)
    {
        $user      = $request->user();
        $personaId = $user->persona_id;

        abort_if(! $personaId, 422);

        $data = $request->validate([
            'preferred_date' => 'required|date|after_or_equal:today',
            'preferred_time' => 'nullable|string|max:10',
            'reason'         => 'nullable|string|max:300',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $id = DB::table('appointment_requests')->insertGetId([
            ...$data,
            'patient_persona_id' => $personaId,
            'status'             => 'pending',
            'created_by'         => $user->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // Notificar al paciente
        $patientEmail = $this->resolvePatientEmail($personaId, $user);
        if ($patientEmail) {
            try {
                $patient = DB::table('personas')->where('id', $personaId)->first();
                Mail::to($patientEmail)->send(new AppointmentRequestReceivedMail([
                    'patient_name'   => trim(($patient->nombres ?? '').' '.($patient->apellido_paterno ?? '')),
                    'preferred_date' => $data['preferred_date'],
                    'preferred_time' => $data['preferred_time'] ?? null,
                    'reason'         => $data['reason'] ?? null,
                    'login_url'      => url('/mi-portal'),
                ]));
            } catch (Throwable $e) {
                Log::error('AppointmentRequest: correo no enviado', ['id' => $id, 'error' => $e->getMessage()]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['id' => $id, 'ok' => true]);
        }

        return back()->with('success', 'Solicitud de cita enviada. Te notificaremos cuando sea revisada.');
    }

    // PATCH /solicitudes-cita/{id}/aprobar  (admin/recepcion)
    public function aprobar(Request $request, int $id)
    {
        $solicitud = DB::table('appointment_requests')->where('id', $id)->first();
        abort_if(! $solicitud || $solicitud->status !== 'pending', 422);

        $data = $request->validate([
            'therapist_user_id' => 'nullable|integer|exists:users,id',
            'start_at'          => 'required|date',
            'end_at'            => 'nullable|date|after:start_at',
            'notes'             => 'nullable|string|max:500',
        ]);

        $startAt = Carbon::parse($data['start_at']);
        $endAt   = isset($data['end_at']) ? Carbon::parse($data['end_at']) : $startAt->copy()->addMinutes(60);

        $therapistId = $data['therapist_user_id'] ?? null;

        if (! $therapistId) {
            $therapistId = DB::table('role_user as ru')
                ->join('roles as r', 'r.id', '=', 'ru.role_id')
                ->join('users as u', 'u.id', '=', 'ru.user_id')
                ->where('r.slug', 'terapeuta')
                ->where('u.status', 'active')
                ->value('u.id');
        }

        if (! $therapistId) {
            $therapistId = $request->user()->id;
        }

        // Crear la cita real
        $apptId = DB::table('appointments')->insertGetId([
            'patient_persona_id' => $solicitud->patient_persona_id,
            'therapist_user_id'  => $therapistId,
            'start_at'           => $startAt,
            'end_at'             => $endAt,
            'status'             => 'confirmed',
            'notes'              => $data['notes'] ?? null,
            'created_by'         => $request->user()->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // Actualizar la solicitud
        DB::table('appointment_requests')->where('id', $id)->update([
            'status'                  => 'approved',
            'approved_appointment_id' => $apptId,
            'reviewed_by'             => $request->user()->id,
            'reviewed_at'             => now(),
            'updated_at'              => now(),
        ]);

        app(AuditLogService::class)->statusChanged(
            $request, 'SolicitudesCita', 'appointment_request', $id,
            'Solicitud de cita aprobada y cita creada.',
            'pending', 'approved'
        );

        // Correo al paciente
        $this->sendApprovedMail($solicitud, [
            'therapist_user_id' => $therapistId,
            'start_at'          => $startAt->toDateTimeString(),
            'end_at'            => $endAt->toDateTimeString(),
            'notes'             => $data['notes'] ?? null,
        ], $apptId, $request->user()->name ?? '');

        if ($request->wantsJson()) {
            return response()->json(['appointment_id' => $apptId, 'ok' => true]);
        }

        return back()->with('success', 'Solicitud aprobada y cita creada.');
    }

    // PATCH /solicitudes-cita/{id}/rechazar  (admin/recepcion)
    public function rechazar(Request $request, int $id)
    {
        $solicitud = DB::table('appointment_requests')->where('id', $id)->first();
        abort_if(! $solicitud || $solicitud->status !== 'pending', 422);

        $data = $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        DB::table('appointment_requests')->where('id', $id)->update([
            'status'           => 'rejected',
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'reviewed_by'      => $request->user()->id,
            'reviewed_at'      => now(),
            'updated_at'       => now(),
        ]);

        app(AuditLogService::class)->statusChanged(
            $request, 'SolicitudesCita', 'appointment_request', $id,
            'Solicitud de cita rechazada.',
            'pending', 'rejected'
        );

        // Correo al paciente
        $this->sendRejectedMail($solicitud, $data['rejection_reason'] ?? null);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Solicitud rechazada.');
    }

    // PATCH /solicitudes-cita/{id}/cancelar  (paciente propio)
    public function cancelar(Request $request, int $id)
    {
        $user      = $request->user();
        $personaId = $user->persona_id;

        $solicitud = DB::table('appointment_requests')
            ->where('id', $id)
            ->where('patient_persona_id', $personaId)
            ->where('status', 'pending')
            ->first();

        abort_if(! $solicitud, 422);

        DB::table('appointment_requests')->where('id', $id)->update([
            'status'     => 'cancelled',
            'updated_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Solicitud cancelada.');
    }

    private function resolvePatientEmail(int $personaId, $user): ?string
    {
        $persona = DB::table('personas')->where('id', $personaId)->first();
        return $persona->email ?? $user->email ?? null;
    }

    private function sendApprovedMail(object $solicitud, array $apptData, int $apptId, string $reviewerName): void
    {
        try {
            $persona      = DB::table('personas')->where('id', $solicitud->patient_persona_id)->first();
            $therapist    = DB::table('users')->where('id', $apptData['therapist_user_id'])->first();
            $patientEmail = $persona->email ?? null;

            if (! $patientEmail) {
                $linkedUser = DB::table('users')->where('persona_id', $solicitud->patient_persona_id)->first();
                $patientEmail = $linkedUser?->email;
            }

            if ($patientEmail) {
                Mail::to($patientEmail)->send(new AppointmentRequestApprovedMail([
                    'patient_name'   => trim(($persona->nombres ?? '').' '.($persona->apellido_paterno ?? '')),
                    'therapist_name' => $therapist->name ?? '',
                    'start_at'       => $apptData['start_at'],
                    'end_at'         => $apptData['end_at'],
                    'notes'          => $apptData['notes'] ?? null,
                    'login_url'      => url('/mi-portal'),
                ]));
            }
        } catch (Throwable $e) {
            Log::error('AppointmentRequest approved mail failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendRejectedMail(object $solicitud, ?string $reason): void
    {
        try {
            $persona      = DB::table('personas')->where('id', $solicitud->patient_persona_id)->first();
            $patientEmail = $persona->email ?? null;

            if (! $patientEmail) {
                $linkedUser = DB::table('users')->where('persona_id', $solicitud->patient_persona_id)->first();
                $patientEmail = $linkedUser?->email;
            }

            if ($patientEmail) {
                Mail::to($patientEmail)->send(new AppointmentRequestRejectedMail([
                    'patient_name'     => trim(($persona->nombres ?? '').' '.($persona->apellido_paterno ?? '')),
                    'preferred_date'   => $solicitud->preferred_date,
                    'rejection_reason' => $reason,
                    'login_url'        => url('/mi-portal'),
                ]));
            }
        } catch (Throwable $e) {
            Log::error('AppointmentRequest rejected mail failed', ['error' => $e->getMessage()]);
        }
    }
}
