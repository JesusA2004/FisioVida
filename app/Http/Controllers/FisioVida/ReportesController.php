<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Services\Reportes\ReportesService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportesController extends Controller
{
    public function __construct(private readonly ReportesService $reportesService)
    {
    }

    public function index(Request $request): Response
    {
        $payload = $this->reportesService->build($request->user(), $request->only([
            'start_date',
            'end_date',
            'status',
            'therapist_user_id',
            'patient_persona_id',
        ]));

        return Inertia::render('Reportes/Index', $payload);
    }
}
