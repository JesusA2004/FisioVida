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
        $filters = $request->only(['start_date', 'end_date', 'therapist_user_id', 'appointment_status']);

        return Inertia::render('Dashboard', $this->metricsService->buildForUser($request->user(), $filters));
    }
}
