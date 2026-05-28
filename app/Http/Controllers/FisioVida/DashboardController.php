<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private DashboardMetricsService $metricsService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // Pacientes puros → redirigir al portal
        if ($user && ! $user->isSuperAdmin() && $user->hasPermission('patient_portal.view')) {
            $adminPermissions = [
                'patients.view', 'sessions.view', 'appointments.view',
                'exercises.view', 'files.view', 'payments.view',
                'users.view', 'roles.view', 'settings.view', 'reports.view',
            ];
            $hasAdminAccess = collect($adminPermissions)->some(fn ($p) => $user->hasPermission($p));
            if (! $hasAdminAccess) {
                return redirect()->route('paciente.portal');
            }
        }

        $filters = $request->only(['start_date', 'end_date', 'therapist_user_id', 'appointment_status']);

        // Terapeuta users see their own stats by default without needing to select themselves
        if (! $user->isSuperAdmin() && ! isset($filters['therapist_user_id'])) {
            $isTherapistUser = DB::table('role_user')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->where('role_user.user_id', $user->id)
                ->whereRaw("LOWER(roles.slug) IN ('terapeuta', 'therapist', 'fisioterapeuta')")
                ->exists();
            if ($isTherapistUser) {
                $filters['therapist_user_id'] = $user->id;
            }
        }

        return Inertia::render('Dashboard', $this->metricsService->buildForUser($user, $filters));
    }
}
