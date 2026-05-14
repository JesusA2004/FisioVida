<?php

namespace App\Http\Controllers\FisioVida;

use App\Exports\FisioVidaReportExport;
use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogService;
use App\Services\Reportes\ReportesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ReportesController extends Controller
{
    public function __construct(private readonly ReportesService $reportesService)
    {
    }

    private function filterKeys(): array
    {
        return ['start_date', 'end_date', 'appointment_status', 'payment_status', 'activity_status', 'therapist_user_id', 'patient_persona_id'];
    }

    public function index(Request $request): Response
    {
        $payload = $this->reportesService->build($request->user(), $request->only($this->filterKeys()));

        return Inertia::render('Reportes/Index', $payload);
    }

    public function exportExcel(Request $request)
    {
        $payload = $this->reportesService->build($request->user(), $request->only($this->filterKeys()));

        app(AuditLogService::class)->record([
            'user_id' => $request->user()?->id,
            'action' => 'Exportación Excel',
            'module' => 'Reportes',
            'auditable_type' => 'report',
            'auditable_id' => null,
            'human_message' => 'El usuario '.$request->user()?->name.' exportó un reporte en Excel.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $filename = 'reporte-fisiovida-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new FisioVidaReportExport($payload), $filename);
    }

    public function exportPdf(Request $request)
    {
        $payload = $this->reportesService->build($request->user(), $request->only($this->filterKeys()));

        app(AuditLogService::class)->record([
            'user_id' => $request->user()?->id,
            'action' => 'Exportación PDF',
            'module' => 'Reportes',
            'auditable_type' => 'report',
            'auditable_id' => null,
            'human_message' => 'El usuario '.$request->user()?->name.' exportó un reporte en PDF.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $clinicName = DB::table('system_settings')->where('key', 'clinic_name')->value('value') ?? 'FisioVida';

        $pdf = Pdf::loadView('reports.fisiovida', array_merge($payload, [
            'clinicName' => $clinicName,
        ]))->setPaper('a4', 'portrait');

        $filename = 'reporte-fisiovida-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
