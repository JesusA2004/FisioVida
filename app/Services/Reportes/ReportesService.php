<?php

namespace App\Services\Reportes;

use App\Models\ModuleSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportesService
{
    public function build(?User $user, array $filters): array
    {
        $now = now();
        $start = $this->parseDate($filters['start_date'] ?? null, $now->copy()->startOfMonth());
        $end = $this->parseDate($filters['end_date'] ?? null, $now->copy()->endOfMonth());

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd = $end->copy()->endOfDay();

        $normalizedFilters = [
            'start_date' => $rangeStart->toDateString(),
            'end_date' => $rangeEnd->toDateString(),
            'status' => trim((string) ($filters['status'] ?? '')),
            'therapist_user_id' => $this->toNullableInt($filters['therapist_user_id'] ?? null),
            'patient_persona_id' => $this->toNullableInt($filters['patient_persona_id'] ?? null),
        ];

        $enabledModules = Schema::hasTable('module_settings') ? ModuleSetting::enabledMap() : [];

        $canAppointments = $this->canSee($user, $enabledModules, 'agenda', 'appointments.view');
        $canSessions = $this->canSee($user, $enabledModules, 'sesiones', 'sessions.view');
        $canPatients = $this->canSee($user, $enabledModules, 'pacientes', 'patients.view');
        $canPayments = $this->canSee($user, $enabledModules, 'pagos', 'payments.view');
        $canActivities = $this->canSee($user, $enabledModules, 'actividades', 'activities.view');

        $appointmentsByStatus = [];
        $appointmentsTotal = 0;
        if ($canAppointments && Schema::hasTable('appointments')) {
            $query = DB::table('appointments')->whereBetween('start_at', [$rangeStart, $rangeEnd]);

            if ($normalizedFilters['therapist_user_id'] && Schema::hasColumn('appointments', 'therapist_user_id')) {
                $query->where('therapist_user_id', $normalizedFilters['therapist_user_id']);
            }

            if ($normalizedFilters['patient_persona_id'] && Schema::hasColumn('appointments', 'patient_persona_id')) {
                $query->where('patient_persona_id', $normalizedFilters['patient_persona_id']);
            }

            if ($normalizedFilters['status'] !== '') {
                $query->where('status', $normalizedFilters['status']);
            }

            $appointmentsByStatus = $query
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->orderBy('status')
                ->get()
                ->map(fn ($row) => ['status' => $row->status, 'total' => (int) $row->total])
                ->values()
                ->all();

            $appointmentsTotal = array_sum(array_column($appointmentsByStatus, 'total'));
        }

        $sessionsTotal = 0;
        $sessionsSummary = ['total' => 0];
        $therapistProductivity = [];
        if ($canSessions && Schema::hasTable('therapy_sessions')) {
            $sessionQuery = DB::table('therapy_sessions')->whereBetween('session_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()]);

            if ($normalizedFilters['therapist_user_id'] && Schema::hasColumn('therapy_sessions', 'therapist_user_id')) {
                $sessionQuery->where('therapist_user_id', $normalizedFilters['therapist_user_id']);
            }

            if ($normalizedFilters['patient_persona_id'] && Schema::hasColumn('therapy_sessions', 'patient_persona_id')) {
                $sessionQuery->where('patient_persona_id', $normalizedFilters['patient_persona_id']);
            }

            $sessionsTotal = (int) (clone $sessionQuery)->count();
            $sessionsSummary = ['total' => $sessionsTotal];

            if (Schema::hasTable('users') && Schema::hasColumn('therapy_sessions', 'therapist_user_id')) {
                $therapistProductivity = (clone $sessionQuery)
                    ->join('users as u', 'u.id', '=', 'therapy_sessions.therapist_user_id')
                    ->select('therapy_sessions.therapist_user_id', 'u.name as therapist_name', DB::raw('COUNT(*) as total'))
                    ->groupBy('therapy_sessions.therapist_user_id', 'u.name')
                    ->orderByDesc('total')
                    ->limit(10)
                    ->get()
                    ->map(fn ($row) => [
                        'therapist_user_id' => (int) $row->therapist_user_id,
                        'therapist_name' => $row->therapist_name,
                        'total' => (int) $row->total,
                    ])
                    ->values()
                    ->all();
            }
        }

        $activePatients = 0;
        $newPatients = 0;
        $patientsSummary = ['active' => 0, 'new' => 0];
        if ($canPatients && Schema::hasTable('personas')) {
            $activePatients = (int) DB::table('personas')
                ->whereNull('deleted_at')
                ->whereIn('tipo', ['paciente', 'ambos'])
                ->where('status', 'active')
                ->count();

            $newPatientsQuery = DB::table('personas')
                ->whereNull('deleted_at')
                ->whereIn('tipo', ['paciente', 'ambos'])
                ->whereBetween('created_at', [$rangeStart, $rangeEnd]);

            if ($normalizedFilters['patient_persona_id']) {
                $newPatientsQuery->where('id', $normalizedFilters['patient_persona_id']);
            }

            $newPatients = (int) $newPatientsQuery->count();
            $patientsSummary = ['active' => $activePatients, 'new' => $newPatients];
        }

        $income = 0.0;
        $paymentsByStatus = [];
        $paymentsSummary = ['income' => 0.0, 'by_status' => []];
        if ($canPayments && Schema::hasTable('payments')) {
            $paymentRange = DB::table('payments')->whereBetween('created_at', [$rangeStart, $rangeEnd]);

            if ($normalizedFilters['status'] !== '') {
                $paymentRange->where('status', $normalizedFilters['status']);
            }

            $paymentsByStatus = (clone $paymentRange)
                ->select('status', DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(amount),0) as amount'))
                ->groupBy('status')
                ->orderBy('status')
                ->get()
                ->map(fn ($row) => [
                    'status' => $row->status,
                    'total' => (int) $row->total,
                    'amount' => (float) $row->amount,
                ])
                ->values()
                ->all();

            $income = (float) DB::table('payments')
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->where('status', 'paid')
                ->sum('amount');

            $paymentsSummary = ['income' => $income, 'by_status' => $paymentsByStatus];
        }

        $activitiesByStatus = [];
        $overdueActivities = 0;
        $activitiesSummary = ['overdue' => 0, 'by_status' => []];
        if ($canActivities && Schema::hasTable('activities')) {
            $activitiesQuery = DB::table('activities')->whereBetween('created_at', [$rangeStart, $rangeEnd]);

            if ($normalizedFilters['status'] !== '') {
                $activitiesQuery->where('status', $normalizedFilters['status']);
            }

            $activitiesByStatus = (clone $activitiesQuery)
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->orderBy('status')
                ->get()
                ->map(fn ($row) => ['status' => $row->status, 'total' => (int) $row->total])
                ->values()
                ->all();

            $overdueActivities = (int) DB::table('activities')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->whereIn('status', ['pending', 'in_progress', 'on_hold', 'overdue'])
                ->count();

            $activitiesSummary = ['overdue' => $overdueActivities, 'by_status' => $activitiesByStatus];
        }

        $lookups = [
            'therapists' => [],
            'patients' => [],
        ];

        if (Schema::hasTable('users') && ($canAppointments || $canSessions)) {
            $lookups['therapists'] = DB::table('users')
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->orderBy('name')
                ->limit(250)
                ->get(['id', DB::raw('name as label')])
                ->map(fn ($row) => ['id' => (int) $row->id, 'label' => $row->label])
                ->values()
                ->all();
        }

        if (Schema::hasTable('personas') && ($canAppointments || $canSessions || $canPatients)) {
            $lookups['patients'] = DB::table('personas')
                ->whereNull('deleted_at')
                ->whereIn('tipo', ['paciente', 'ambos'])
                ->orderBy('apellido_paterno')
                ->limit(250)
                ->get(['id', DB::raw("TRIM(CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno)) as label")])
                ->map(fn ($row) => ['id' => (int) $row->id, 'label' => $row->label])
                ->values()
                ->all();
        }

        return [
            'filters' => $normalizedFilters,
            'summary' => [
                'appointments_period' => $appointmentsTotal,
                'sessions_period' => $sessionsTotal,
                'active_patients' => $activePatients,
                'new_patients_period' => $newPatients,
                'income_period' => $income,
                'activities_overdue' => $overdueActivities,
            ],
            'appointmentsByStatus' => $appointmentsByStatus,
            'sessionsSummary' => $sessionsSummary,
            'patientsSummary' => $patientsSummary,
            'paymentsSummary' => $paymentsSummary,
            'activitiesSummary' => $activitiesSummary,
            'therapistProductivity' => $therapistProductivity,
            'lookups' => $lookups,
        ];
    }

    private function canSee(?User $user, array $enabledModules, string $moduleKey, string $permission): bool
    {
        if (($enabledModules[$moduleKey] ?? true) === false) {
            return false;
        }

        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasPermission($permission);
    }

    private function parseDate(?string $value, Carbon $fallback): Carbon
    {
        try {
            return $value ? Carbon::parse($value) : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
