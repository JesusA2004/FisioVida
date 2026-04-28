<?php

namespace App\Services\Dashboard;

use App\Models\ModuleSetting;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardMetricsService
{
    public function buildForUser(User $user): array
    {
        $enabledModules = Schema::hasTable('module_settings')
            ? ModuleSetting::enabledMap()
            : [];

        $appSettings = Schema::hasTable('system_settings')
            ? SystemSetting::keyValuePublic()
            : [];

        $canPatients = $this->can($user, $enabledModules, 'pacientes', 'patients.view');
        $canAppointments = $this->can($user, $enabledModules, 'agenda', 'appointments.view');
        $canSessions = $this->can($user, $enabledModules, 'sesiones', 'sessions.view');
        $canPayments = $this->can($user, $enabledModules, 'pagos', 'payments.view');
        $canActivities = $this->can($user, $enabledModules, 'actividades', 'activities.view');

        $stats = [
            'activePatients' => $canPatients ? $this->countActivePatients() : null,
            'todayAppointments' => $canAppointments ? $this->countTodayAppointments() : null,
            'pendingAppointments' => $canAppointments ? $this->countPendingAppointments() : null,
            'cancelledAppointments' => $canAppointments ? $this->countCancelledAppointments() : null,
            'monthSessions' => $canSessions ? $this->countMonthSessions() : null,
            'pendingPayments' => $canPayments ? $this->countPendingPayments() : null,
            'monthIncome' => $canPayments ? $this->sumMonthIncome() : null,
            'pendingActivities' => $canActivities ? $this->countPendingActivities() : null,
            'overdueActivities' => $canActivities ? $this->countOverdueActivities() : null,
            'activeTherapists' => $canSessions ? $this->countActiveTherapists() : null,
        ];

        $todayAppointments = $canAppointments ? $this->todayAppointments() : [];
        $upcomingAppointments = $canAppointments ? $this->upcomingAppointments() : [];
        $pendingActivities = $canActivities ? $this->pendingActivities() : [];
        $overdueActivities = $canActivities ? $this->overdueActivities() : [];
        $recentPayments = $canPayments ? $this->recentPayments() : [];

        $alerts = [];
        if (($stats['overdueActivities'] ?? 0) > 0) {
            $alerts[] = ['type' => 'warning', 'text' => 'Hay actividades vencidas que requieren atención inmediata.'];
        }
        if (($stats['pendingPayments'] ?? 0) > 0) {
            $alerts[] = ['type' => 'info', 'text' => 'Existen pagos pendientes por revisar.'];
        }
        if (($stats['todayAppointments'] ?? 0) > 0) {
            $alerts[] = ['type' => 'success', 'text' => 'Tienes citas programadas para hoy.'];
        }
        if (($appSettings['demo_mode'] ?? '0') === '1') {
            $alerts[] = ['type' => 'info', 'text' => 'Modo demo activo: los datos pueden ser ficticios.'];
        }

        return [
            'stats' => $stats,
            'todayAppointments' => $todayAppointments,
            'upcomingAppointments' => $upcomingAppointments,
            'pendingActivities' => $pendingActivities,
            'overdueActivities' => $overdueActivities,
            'recentPayments' => $recentPayments,
            'alerts' => $alerts,
            'enabledModules' => $enabledModules,
            'appSettings' => $appSettings,
        ];
    }

    private function can(User $user, array $enabledModules, string $module, string $permission): bool
    {
        $moduleEnabled = ! array_key_exists($module, $enabledModules) || $enabledModules[$module] === true;

        return $moduleEnabled && $user->hasPermission($permission);
    }

    private function countActivePatients(): int
    {
        if (! Schema::hasTable('personas')) return 0;

        return DB::table('personas')
            ->whereNull('deleted_at')
            ->whereIn('tipo', ['paciente', 'ambos'])
            ->where('status', 'active')
            ->count();
    }

    private function countTodayAppointments(): int
    {
        if (! Schema::hasTable('appointments')) return 0;

        return DB::table('appointments')->whereDate('start_at', now()->toDateString())->count();
    }

    private function countPendingAppointments(): int
    {
        if (! Schema::hasTable('appointments')) return 0;

        return DB::table('appointments')->whereIn('status', ['scheduled', 'confirmed'])->count();
    }

    private function countCancelledAppointments(): int
    {
        if (! Schema::hasTable('appointments')) return 0;

        return DB::table('appointments')->where('status', 'cancelled')->count();
    }

    private function countMonthSessions(): int
    {
        if (! Schema::hasTable('therapy_sessions')) return 0;

        return DB::table('therapy_sessions')
            ->whereBetween('session_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    private function countPendingPayments(): int
    {
        if (! Schema::hasTable('payments')) return 0;

        return DB::table('payments')->where('status', 'pending')->count();
    }

    private function sumMonthIncome(): float
    {
        if (! Schema::hasTable('payments')) return 0;

        return (float) DB::table('payments')
            ->where('status', 'paid')
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
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

    private function todayAppointments(): array
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

        return $query
            ->whereDate('a.start_at', now()->toDateString())
            ->orderBy('a.start_at')
            ->limit(8)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function upcomingAppointments(): array
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

        return $query
            ->where('a.start_at', '>', now())
            ->whereIn('a.status', ['scheduled', 'confirmed'])
            ->orderBy('a.start_at')
            ->limit(8)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function pendingActivities(): array
    {
        if (! Schema::hasTable('activities')) return [];

        return DB::table('activities as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.responsible_user_id')
            ->select(['a.id', 'a.title', 'a.priority', 'a.status', 'a.due_date', 'u.name as responsible_name'])
            ->whereIn('a.status', ['pending', 'in_progress', 'on_hold'])
            ->orderBy('a.due_date')
            ->limit(8)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function overdueActivities(): array
    {
        if (! Schema::hasTable('activities')) return [];

        return DB::table('activities as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.responsible_user_id')
            ->select(['a.id', 'a.title', 'a.priority', 'a.status', 'a.due_date', 'u.name as responsible_name'])
            ->whereIn('a.status', ['pending', 'in_progress', 'on_hold'])
            ->whereNotNull('a.due_date')
            ->where('a.due_date', '<', now())
            ->orderBy('a.due_date')
            ->limit(8)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function recentPayments(): array
    {
        if (! Schema::hasTable('payments')) return [];

        $query = DB::table('payments as p')
            ->select(['p.id', 'p.amount', 'p.currency', 'p.status', 'p.paid_at', 'p.reference']);

        if (Schema::hasTable('personas') && Schema::hasColumn('payments', 'patient_persona_id')) {
            $query->leftJoin('personas as pe', 'pe.id', '=', 'p.patient_persona_id')
                ->addSelect(DB::raw("TRIM(CONCAT_WS(' ', pe.nombres, pe.apellido_paterno, pe.apellido_materno)) as patient_name"));
        } else {
            $query->addSelect(DB::raw("NULL as patient_name"));
        }

        return $query
            ->orderByDesc('p.id')
            ->limit(8)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }
}
