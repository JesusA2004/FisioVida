<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\Http\Request;
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

        return Inertia::render('Dashboard', $this->metricsService->buildForUser($user, $filters));
    }
}
