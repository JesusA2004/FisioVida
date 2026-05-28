<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use App\Http\Requests\Citas\CitaStoreRequest;
use App\Http\Requests\Citas\CitaUpdateRequest;
use App\Http\Resources\CitaResource;
use App\Mail\AppointmentCancelledMail;
use App\Mail\AppointmentConfirmedMail;
use App\Mail\AppointmentScheduledMail;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Throwable;

class CitasController extends Controller
{
    use CrudHelpers;

    public function index(Request $request)
    {
        $q = $this->like($request->string('q'));
        $status = $request->string('status')->toString();
        $perPageInput = $request->input('per_page', 10);
        $dateFrom = $request->string('date_from')->toString();
        $dateTo   = $request->string('date_to')->toString();
        $therapistFilter = $request->string('therapist_user_id')->toString();

        // For therapist-role users, silently scope to their own appointments
        $currentUser = $request->user();
        if (! $currentUser->isSuperAdmin() && $therapistFilter === '') {
            $isTherapistUser = DB::table('role_user')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->where('role_user.user_id', $currentUser->id)
                ->whereRaw("LOWER(roles.slug) IN ('terapeuta', 'therapist', 'fisioterapeuta')")
                ->exists();
            if ($isTherapistUser) {
                $therapistFilter = (string) $currentUser->id;
            }
        }

        $query = DB::table('appointments as a')
            ->join('personas as p', 'p.id', '=', 'a.patient_persona_id')
            ->join('users as u', 'u.id', '=', 'a.therapist_user_id')
            ->whereNull('p.deleted_at')
            ->whereNull('u.deleted_at')
            ->select([
                'a.id',
                'a.patient_persona_id',
                'a.therapist_user_id',
                'a.start_at',
                'a.end_at',
                'a.status',
                'a.notes',
                'a.created_by',
                DB::raw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) as patient_name"),
                'u.name as therapist_name',
                'a.created_at',
                'a.updated_at',
            ])
            ->orderByDesc('a.start_at');

        if ($status !== '') {
            $query->where('a.status', $status);
        }

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('p.nombres', 'like', $q)
                    ->orWhere('p.apellido_paterno', 'like', $q)
                    ->orWhere('p.apellido_materno', 'like', $q)
                    ->orWhere('u.name', 'like', $q);
            });
        }

        if ($dateFrom !== '') {
            $query->where('a.start_at', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo !== '') {
            $query->where('a.start_at', '<=', $dateTo . ' 23:59:59');
        }
        if ($therapistFilter !== '') {
            $query->where('a.therapist_user_id', (int) $therapistFilter);
        }

        if ($perPageInput === 'all') {
            $total = (clone $query)->count();
            $perPage = max(1, min($total, 500));
        } else {
            $perPage = (int) $perPageInput;
            $perPage = in_array($perPage, [10, 15, 20, 50], true)
                ? $perPage
                : 10;
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $patients = DB::table('personas')
            ->whereNull('deleted_at')
            ->whereIn('tipo', ['paciente', 'ambos'])
            ->where('status', 'active')
            ->orderBy('apellido_paterno')
            ->limit(200)
            ->get([
                'id',
                DB::raw("TRIM(CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno)) as label"),
            ]);

        $therapists = DB::table('users')
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(200)
            ->get([
                'id',
                DB::raw('name as label'),
            ]);

        return Inertia::render('Agenda/Index', [
            'rows' => CitaResource::collection(collect($paginator->items()))->resolve(),
            'page' => [
                ...$this->packPaginator($paginator),
                'per_page_selected' => $perPageInput === 'all' ? 'all' : $perPage,
            ],
            'filters' => $this->filters($request, ['q', 'status', 'per_page', 'date_from', 'date_to', 'therapist_user_id']),
            'lookups' => [
                'patients' => $patients,
                'therapists' => $therapists,
            ],
        ]);
    }

    public function store(CitaStoreRequest $request)
    {
        $payload = $request->validated();

        // Si el usuario tiene rol terapeuta y no es superadmin, forzar su propio ID.
        $user = $request->user();
        if ($user && ! $user->isSuperAdmin()) {
            $isTherapist = $user->roles()->whereIn('slug', ['terapeuta', 'therapist'])->exists();
            if ($isTherapist) {
                $payload['therapist_user_id'] = $user->id;
            }
        }

        if ($this->hasOverlap((int) $payload['therapist_user_id'], $payload['start_at'], $payload['end_at'])) {
            return back()->withErrors(['start_at' => 'El terapeuta ya tiene una cita en ese horario.']);
        }

        // Toda cita nueva inicia como Programada.
        $payload['status'] = 'scheduled';
        $payload['created_by'] = $request->user()?->id;
        $payload['created_at'] = now();
        $payload['updated_at'] = now();

        DB::table('appointments')->insert($payload);

        $id = (int) DB::getPdo()->lastInsertId();

        app(AuditLogService::class)->created(
            $request,
            'Agenda',
            'appointment',
            $id,
            'El usuario '.$request->user()?->name.' registró una cita clínica.',
            $payload,
        );

        $appointment = $this->getAppointmentNotificationData($id);

        if ($appointment) {
            try {
                $recipients = collect([
                    $appointment['patient_email'] ?? null,
                    $appointment['therapist_email'] ?? null,
                ])
                    ->filter()
                    ->unique()
                    ->values();

                foreach ($recipients as $email) {
                    Mail::to($email)->send(
                        new AppointmentScheduledMail($appointment)
                    );
                }
            } catch (Throwable $exception) {
                Log::error('No se pudo enviar el correo de cita agendada.', [
                    'appointment_id' => $id,
                    'message' => $exception->getMessage(),
                ]);

                return back()->with(
                    'warning',
                    'Cita creada, pero no se pudo enviar el correo de notificación. Revisa la configuración de correo.'
                );
            }
        }

        return back()->with('success', 'Cita creada y notificación enviada.');
    }

    public function update(CitaUpdateRequest $request, string $id)
    {
        $old = (array) DB::table('appointments')->where('id', $id)->first();

        abort_if(empty($old), 404);

        $payload = $request->validated();

        if ($this->hasOverlap((int) $payload['therapist_user_id'], $payload['start_at'], $payload['end_at'], (int) $id)) {
            return back()->withErrors(['start_at' => 'El terapeuta ya tiene una cita en ese horario.']);
        }

        $payload['updated_at'] = now();

        DB::table('appointments')
            ->where('id', $id)
            ->update($payload);

        $from = (string) ($old['status'] ?? '');
        $to = (string) ($payload['status'] ?? $from);

        if ($from !== '' && $to !== '' && $from !== $to) {
            app(AuditLogService::class)->statusChanged(
                $request,
                'Agenda',
                'appointment',
                (int) $id,
                'El usuario '.$request->user()?->name.' cambió el estado de una cita de '.$from.' a '.$to.'.',
                $from,
                $to,
            );
        } else {
            app(AuditLogService::class)->updated(
                $request,
                'Agenda',
                'appointment',
                (int) $id,
                'El usuario '.$request->user()?->name.' actualizó una cita.',
                $old,
                $payload,
            );
        }

        return back()->with('success', 'Cita actualizada.');
    }

    public function destroy(Request $request, string $id)
    {
        $old = (array) DB::table('appointments')->where('id', $id)->first();

        abort_if(empty($old), 404);

        DB::table('appointments')
            ->where('id', $id)
            ->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);

        app(AuditLogService::class)->statusChanged(
            $request,
            'Agenda',
            'appointment',
            (int) $id,
            'El usuario '.$request->user()?->name.' canceló una cita.',
            (string) ($old['status'] ?? ''),
            'cancelled',
        );

        return back()->with('success', 'Cita cancelada correctamente.');
    }

    public function cancelar(Request $request, string $id)
    {
        $old = (array) DB::table('appointments')->where('id', $id)->first();

        abort_if(empty($old), 404);

        if (in_array($old['status'], ['cancelled', 'done', 'no_show'], true)) {
            return back()->with('warning', 'Esta cita ya está en un estado final y no puede cancelarse.');
        }

        DB::table('appointments')
            ->where('id', $id)
            ->update(['status' => 'cancelled', 'updated_at' => now()]);

        app(AuditLogService::class)->statusChanged(
            $request,
            'Agenda',
            'appointment',
            (int) $id,
            'El usuario '.$request->user()?->name.' canceló una cita.',
            (string) ($old['status'] ?? ''),
            'cancelled',
        );

        $this->sendStatusMail('cancelled', (int) $id);

        return back()->with('success', 'Cita cancelada.');
    }

    public function noShow(Request $request, string $id)
    {
        $old = (array) DB::table('appointments')->where('id', $id)->first();

        abort_if(empty($old), 404);

        if (in_array($old['status'], ['cancelled', 'done', 'no_show'], true)) {
            return back()->with('warning', 'Esta cita ya está en un estado final.');
        }

        DB::table('appointments')
            ->where('id', $id)
            ->update(['status' => 'no_show', 'updated_at' => now()]);

        app(AuditLogService::class)->statusChanged(
            $request,
            'Agenda',
            'appointment',
            (int) $id,
            'El usuario '.$request->user()?->name.' marcó una cita como no asistida.',
            (string) ($old['status'] ?? ''),
            'no_show',
        );

        return back()->with('success', 'Cita marcada como no asistida.');
    }

    public function avanzar(Request $request, string $id)
    {
        $old = (array) DB::table('appointments')->where('id', $id)->first();

        abort_if(empty($old), 404);

        $transitions = [
            'scheduled' => 'confirmed',
            'confirmed' => 'arrived',
            'arrived' => 'done',
        ];

        $nextStatus = $transitions[$old['status'] ?? ''] ?? null;

        if (! $nextStatus) {
            return back()->with('warning', 'Esta cita no puede avanzar de estado.');
        }

        DB::table('appointments')
            ->where('id', $id)
            ->update(['status' => $nextStatus, 'updated_at' => now()]);

        app(AuditLogService::class)->statusChanged(
            $request,
            'Agenda',
            'appointment',
            (int) $id,
            'El usuario '.$request->user()?->name.' avanzó el estado de una cita a '.$nextStatus.'.',
            (string) ($old['status'] ?? ''),
            $nextStatus,
        );

        $this->sendStatusMail($nextStatus, (int) $id);

        return back()->with('success', 'Estado de cita actualizado.');
    }

    private function sendStatusMail(string $status, int $appointmentId): void
    {
        try {
            $appointment = $this->getAppointmentNotificationData($appointmentId);
            if (! $appointment) {
                return;
            }

            $mail = match ($status) {
                'confirmed' => new AppointmentConfirmedMail($appointment),
                'cancelled'  => new AppointmentCancelledMail($appointment),
                default      => null,
            };

            if (! $mail) {
                return;
            }

            $recipients = collect([
                $appointment['patient_email'] ?? null,
                $appointment['therapist_email'] ?? null,
            ])->filter()->unique()->values();

            foreach ($recipients as $email) {
                Mail::to($email)->send($mail);
            }
        } catch (Throwable $e) {
            Log::error('CitasController: correo de estado no enviado', [
                'status'         => $status,
                'appointment_id' => $appointmentId,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    private function hasOverlap(int $therapistId, string $startAt, string $endAt, ?int $excludeId = null): bool
    {
        $start = Carbon::parse($startAt)->toDateTimeString();
        $end = Carbon::parse($endAt)->toDateTimeString();

        $query = DB::table('appointments')
            ->where('therapist_user_id', $therapistId)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->whereNotNull('end_at')
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function getAppointmentNotificationData(int $appointmentId): ?array
    {
        $row = DB::table('appointments as a')
            ->join('personas as p', 'p.id', '=', 'a.patient_persona_id')
            ->join('users as u', 'u.id', '=', 'a.therapist_user_id')
            ->where('a.id', $appointmentId)
            ->select([
                'a.id',
                'a.start_at',
                'a.end_at',
                'a.status',
                'a.notes',
                DB::raw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) as patient_name"),
                'p.email as patient_email',
                'p.telefono as patient_phone',
                'u.name as therapist_name',
                'u.email as therapist_email',
            ])
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'id' => $row->id,
            'patient_name' => $row->patient_name,
            'patient_email' => $row->patient_email,
            'patient_phone' => $row->patient_phone,
            'therapist_name' => $row->therapist_name,
            'therapist_email' => $row->therapist_email,
            'start_at' => $row->start_at,
            'end_at' => $row->end_at,
            'status' => $row->status,
            'notes' => $row->notes,
            'login_url' => url('/login'),
        ];
    }
}
