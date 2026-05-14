<?php

namespace App\Services\Dashboard;

use App\Models\ModuleSetting;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardMetricsService
{
    public function buildForUser(User $user, array $filters = []): array
    {
        $enabledModules = Schema::hasTable('module_settings')
            ? ModuleSetting::enabledMap()
            : [];

        $appSettings = Schema::hasTable('system_settings')
            ? SystemSetting::keyValuePublic()
            : [];

        $now = now();
        $startDate       = $filters['start_date'] ?? $now->copy()->startOfMonth()->toDateString();
        $endDate         = $filters['end_date']   ?? $now->copy()->endOfMonth()->toDateString();
        $therapistId     = isset($filters['therapist_user_id']) && $filters['therapist_user_id'] !== ''
            ? (int) $filters['therapist_user_id'] : null;
        $appointmentStatus = trim((string) ($filters['appointment_status'] ?? ''));

        $canPatients     = $this->can($user, $enabledModules, 'pacientes',   'patients.view');
        $canAppointments = $this->can($user, $enabledModules, 'agenda',      'appointments.view');
        $canSessions     = $this->can($user, $enabledModules, 'sesiones',    'sessions.view');
        $canPayments     = $this->can($user, $enabledModules, 'pagos',       'payments.view');
        $canActivities   = $this->can($user, $enabledModules, 'actividades', 'activities.view');

        // ── KPI stats ───────────────────────────────────────────────────────
        $stats = [
            'activePatients'       => $canPatients     ? $this->countActivePatients() : null,
            'todayAppointments'    => $canAppointments ? $this->countTodayAppointments($therapistId, $appointmentStatus) : null,
            'pendingAppointments'  => $canAppointments ? $this->countPendingAppointments($therapistId) : null,
            'cancelledAppointments'=> $canAppointments ? $this->countCancelledAppointments($therapistId) : null,
            'monthSessions'        => $canSessions     ? $this->countMonthSessions($startDate, $endDate, $therapistId) : null,
            'pendingPayments'      => $canPayments     ? $this->countPendingPayments() : null,
            'monthIncome'          => $canPayments     ? $this->sumPeriodIncome($startDate, $endDate) : null,
            'pendingActivities'    => $canActivities   ? $this->countPendingActivities() : null,
            'overdueActivities'    => $canActivities   ? $this->countOverdueActivities() : null,
            'activeTherapists'     => $canSessions     ? $this->countActiveTherapists() : null,
        ];

        // ── Time-series & distribution charts ───────────────────────────────
        $appointmentsByStatus  = $canAppointments ? $this->appointmentsByStatus($startDate, $endDate, $therapistId, $appointmentStatus) : [];
        $sessionsByDay         = $canSessions     ? $this->sessionsByDay($startDate, $endDate, $therapistId) : [];
        $incomeByDay           = $canPayments     ? $this->incomeByDay($startDate, $endDate) : [];
        $activitiesByStatus    = $canActivities   ? $this->activitiesByStatus() : [];
        $paymentsByStatus      = $canPayments     ? $this->paymentsByStatus($startDate, $endDate) : [];
        $therapistProductivity = $canSessions     ? $this->therapistProductivity($startDate, $endDate) : [];

        // ── Compact upcoming list (max 3 items) ──────────────────────────────
        $upcomingAppointments = $canAppointments ? $this->upcomingAppointments($therapistId, 3) : [];

        // ── Alerts ──────────────────────────────────────────────────────────
        $alerts = [];
        if (($stats['overdueActivities'] ?? 0) > 0) {
            $alerts[] = ['type' => 'warning', 'text' => 'Hay actividades vencidas que requieren atención inmediata.'];
        }
        if (($stats['pendingPayments'] ?? 0) > 0) {
            $alerts[] = ['type' => 'info', 'text' => 'Existen pagos pendientes por revisar.'];
        }
        if (($appSettings['demo_mode'] ?? '0') === '1') {
            $alerts[] = ['type' => 'info', 'text' => 'Modo demo activo: los datos pueden ser ficticios.'];
        }

        $normalizedFilters = [
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'therapist_user_id' => $therapistId,
            'appointment_status'=> $appointmentStatus,
        ];

        $therapistLookup = ($canAppointments || $canSessions) && Schema::hasTable('users')
            ? DB::table('users')->whereNull('deleted_at')->where('status', 'active')
                ->orderBy('name')->limit(100)
                ->get(['id', DB::raw('name as label')])
                ->map(fn ($r) => ['id' => (int) $r->id, 'label' => $r->label])
                ->values()->all()
            : [];

        return [
            'stats'                => $stats,
            'upcomingAppointments' => $upcomingAppointments,
            'appointmentsByStatus' => $appointmentsByStatus,
            'sessionsByDay'        => $sessionsByDay,
            'incomeByDay'          => $incomeByDay,
            'activitiesByStatus'   => $activitiesByStatus,
            'paymentsByStatus'     => $paymentsByStatus,
            'therapistProductivity'=> $therapistProductivity,
            'alerts'               => $alerts,
            'enabledModules'       => $enabledModules,
            'appSettings'          => $appSettings,
            'filters'              => $normalizedFilters,
            'therapistLookup'      => $therapistLookup,
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function can(User $user, array $enabledModules, string $module, string $permission): bool
    {
        $moduleEnabled = ! array_key_exists($module, $enabledModules) || $enabledModules[$module] === true;
        return $moduleEnabled && $user->hasPermission($permission);
    }

    // ── Counters ─────────────────────────────────────────────────────────────

    private function countActivePatients(): int
    {
        if (! Schema::hasTable('personas')) return 0;
        return DB::table('personas')
            ->whereNull('deleted_at')
            ->whereIn('tipo', ['paciente', 'ambos'])
            ->where('status', 'active')
            ->count();
    }

    private function countTodayAppointments(?int $therapistId = null, string $status = ''): int
    {
        if (! Schema::hasTable('appointments')) return 0;
        $q = DB::table('appointments')->whereDate('start_at', now()->toDateString());
        if ($therapistId && Schema::hasColumn('appointments', 'therapist_user_id')) {
            $q->where('therapist_user_id', $therapistId);
        }
        if ($status !== '') $q->where('status', $status);
        return $q->count();
    }

    private function countPendingAppointments(?int $therapistId = null): int
    {
        if (! Schema::hasTable('appointments')) return 0;
        $q = DB::table('appointments')->whereIn('status', ['scheduled', 'confirmed']);
        if ($therapistId && Schema::hasColumn('appointments', 'therapist_user_id')) {
            $q->where('therapist_user_id', $therapistId);
        }
        return $q->count();
    }

    private function countCancelledAppointments(?int $therapistId = null): int
    {
        if (! Schema::hasTable('appointments')) return 0;
        $q = DB::table('appointments')->where('status', 'cancelled');
        if ($therapistId && Schema::hasColumn('appointments', 'therapist_user_id')) {
            $q->where('therapist_user_id', $therapistId);
        }
        return $q->count();
    }

    private function countMonthSessions(string $start, string $end, ?int $therapistId = null): int
    {
        if (! Schema::hasTable('therapy_sessions')) return 0;
        $q = DB::table('therapy_sessions')->whereBetween('session_date', [$start, $end]);
        if ($therapistId && Schema::hasColumn('therapy_sessions', 'therapist_user_id')) {
            $q->where('therapist_user_id', $therapistId);
        }
        return $q->count();
    }

    private function countPendingPayments(): int
    {
        if (! Schema::hasTable('payments')) return 0;
        return DB::table('payments')->where('status', 'pending')->count();
    }

    private function sumPeriodIncome(string $start, string $end): float
    {
        if (! Schema::hasTable('payments')) return 0;
        return (float) DB::table('payments')
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->sum('amount');
    }

    private function countPendingActivities(): int
    {
        if (! Schema::hasTable('activities')) return 0;
        return DB::table('activities')->whereIn('status', ['pending', 'in_progress', 'on_hold'])->count();
    }

    private function countOverdueActivities(): int
    {
        if (! Schema::hasTable('activities')) return 0;
        return DB::table('activities')
            ->whereIn('status', ['pending', 'in_progress', 'on_hold'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();
    }

    private function countActiveTherapists(): int
    {
        if (! Schema::hasTable('users')) return 0;
        return DB::table('users')->where('status', 'active')->whereNull('deleted_at')->count();
    }

    // ── Time-series charts ───────────────────────────────────────────────────

    private function appointmentsByStatus(string $start, string $end, ?int $therapistId = null, string $status = ''): array
    {
        if (! Schema::hasTable('appointments')) return [];
        $q = DB::table('appointments')
            ->whereBetween('start_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
        if ($therapistId && Schema::hasColumn('appointments', 'therapist_user_id')) {
            $q->where('therapist_user_id', $therapistId);
        }
        if ($status !== '') $q->where('status', $status);
        return $q->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')->orderBy('status')
            ->get()
            ->map(fn ($r) => ['status' => $r->status, 'total' => (int) $r->total])
            ->values()->all();
    }

    private function sessionsByDay(string $start, string $end, ?int $therapistId = null): array
    {
        if (! Schema::hasTable('therapy_sessions')) return [];
        $q = DB::table('therapy_sessions')->whereBetween('session_date', [$start, $end]);
        if ($therapistId && Schema::hasColumn('therapy_sessions', 'therapist_user_id')) {
            $q->where('therapist_user_id', $therapistId);
        }
        return $q->select('session_date', DB::raw('COUNT(*) as total'))
            ->groupBy('session_date')->orderBy('session_date')
            ->get()
            ->map(fn ($r) => ['date' => $r->session_date, 'total' => (int) $r->total])
            ->values()->all();
    }

    private function incomeByDay(string $start, string $end): array
    {
        if (! Schema::hasTable('payments')) return [];
        return DB::table('payments')
            ->where('status', 'paid')->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('DATE(paid_at)'))->orderBy(DB::raw('DATE(paid_at)'))
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'total' => (float) $r->total])
            ->values()->all();
    }

    // ── New distribution charts ───────────────────────────────────────────────

    private function activitiesByStatus(): array
    {
        if (! Schema::hasTable('activities')) return [];

        $rows = DB::table('activities')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')->orderBy('status')
            ->get()
            ->map(fn ($r) => ['status' => $r->status, 'total' => (int) $r->total])
            ->values()->toArray();

        // Add virtual 'overdue' status (active tasks past due_date)
        $overdue = DB::table('activities')
            ->whereIn('status', ['pending', 'in_progress', 'on_hold'])
            ->whereNotNull('due_date')->where('due_date', '<', now())
            ->count();

        if ($overdue > 0) {
            $rows[] = ['status' => 'overdue', 'total' => $overdue];
        }

        return $rows;
    }

    private function paymentsByStatus(string $start, string $end): array
    {
        if (! Schema::hasTable('payments')) return [];

        return DB::table('payments')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->select('status', DB::raw('COUNT(*) as total'), DB::raw('SUM(amount) as amount'))
            ->groupBy('status')->orderBy('status')
            ->get()
            ->map(fn ($r) => [
                'status' => $r->status,
                'total'  => (int)   $r->total,
                'amount' => (float) ($r->amount ?? 0),
            ])
            ->values()->all();
    }

    private function therapistProductivity(string $start, string $end): array
    {
        if (! Schema::hasTable('therapy_sessions')) return [];

        return DB::table('therapy_sessions as s')
            ->leftJoin('users as u', 'u.id', '=', 's.therapist_user_id')
            ->whereBetween('s.session_date', [$start, $end])
            ->whereNotNull('s.therapist_user_id')
            ->select('u.name as therapist', DB::raw('COUNT(*) as sessions'))
            ->groupBy('s.therapist_user_id', 'u.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(8)
            ->get()
            ->map(fn ($r) => ['therapist' => $r->therapist ?? 'Sin nombre', 'sessions' => (int) $r->sessions])
            ->values()->all();
    }

    // ── Compact list (max 3) ─────────────────────────────────────────────────

    private function upcomingAppointments(?int $therapistId = null, int $limit = 3): array
    {
        if (! Schema::hasTable('appointments')) return [];

        $query = DB::table('appointments as a')
            ->select(['a.id', 'a.start_at', 'a.end_at', 'a.status']);

        if (Schema::hasTable('personas') && Schema::hasColumn('appointments', 'patient_persona_id')) {
            $query->leftJoin('personas as p', 'p.id', '=', 'a.patient_persona_id')
                ->addSelect(DB::raw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) as patient_name"));
        } else {
            $query->addSelect(DB::raw('NULL as patient_name'));
        }

        if (Schema::hasTable('users') && Schema::hasColumn('appointments', 'therapist_user_id')) {
            $query->leftJoin('users as u', 'u.id', '=', 'a.therapist_user_id')
                ->addSelect('u.name as therapist_name');
        } else {
            $query->addSelect(DB::raw('NULL as therapist_name'));
        }

        if ($therapistId && Schema::hasColumn('appointments', 'therapist_user_id')) {
            $query->where('a.therapist_user_id', $therapistId);
        }

        return $query
            ->where('a.start_at', '>', now())
            ->whereIn('a.status', ['scheduled', 'confirmed'])
            ->orderBy('a.start_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }
}
