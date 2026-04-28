<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardMetricsService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private DashboardMetricsService $metricsService)
    {
    }

    public function index()
    {
        return Inertia::render('Dashboard', $this->metricsService->buildForUser(request()->user()));
    }
}
