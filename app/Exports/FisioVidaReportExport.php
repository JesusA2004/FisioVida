<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class FisioVidaReportExport implements WithEvents, WithTitle
{
    use Exportable;

    private array $statusLabels = [
        'scheduled' => 'Programada', 'confirmed' => 'Confirmada', 'done' => 'Realizada',
        'cancelled' => 'Cancelada', 'no_show' => 'No asistió',
        'pending' => 'Pendiente', 'paid' => 'Pagado', 'partial' => 'Parcial', 'refunded' => 'Reembolsado',
        'in_progress' => 'En progreso', 'on_hold' => 'En espera', 'completed' => 'Completada',
        'overdue' => 'Vencida',
    ];

    public function __construct(private readonly array $payload)
    {
    }

    public function title(): string
    {
        return 'Reporte FisioVida';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $filters = $this->payload['filters'] ?? [];
                $summary = $this->payload['summary'] ?? [];
                $clinicName = DB::table('system_settings')->where('key', 'clinic_name')->value('value') ?? 'FisioVida';

                $row = 1;

                // ── Title block ──────────────────────────────────────────────
                $sheet->mergeCells("A{$row}:F{$row}");
                $sheet->setCellValue("A{$row}", strtoupper($clinicName));
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(34);
                $row++;

                $sheet->mergeCells("A{$row}:F{$row}");
                $sheet->setCellValue("A{$row}", 'Reporte Ejecutivo FisioVida');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6366F1']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(22);
                $row++;

                $sheet->mergeCells("A{$row}:F{$row}");
                $sheet->setCellValue("A{$row}", 'Periodo: ' . ($filters['start_date'] ?? '') . ' al ' . ($filters['end_date'] ?? '') . '   |   Generado: ' . now()->format('d/m/Y H:i'));
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font'      => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '64748B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $row++;
                $row++; // blank

                // ── KPI Section ──────────────────────────────────────────────
                $row = $this->sectionHeader($sheet, $row, 'RESUMEN GENERAL', '0F172A', 'E2E8F0');

                $kpis = [
                    ['Pacientes activos',          $summary['active_patients'] ?? 0,         false],
                    ['Pacientes nuevos (periodo)', $summary['new_patients_period'] ?? 0,      false],
                    ['Citas del periodo',          $summary['appointments_period'] ?? 0,      false],
                    ['Sesiones del periodo',       $summary['sessions_period'] ?? 0,          false],
                    ['Ingresos del periodo',       $summary['income_period'] ?? 0,            true],
                    ['Pagos pendientes',           $summary['pending_amount'] ?? 0,           true],
                    ['Total pagos registrados',    $summary['total_payments'] ?? 0,           false],
                    ['Actividades vencidas',       $summary['activities_overdue'] ?? 0,       false],
                ];

                $sheet->setCellValue("A{$row}", 'Indicador');
                $sheet->setCellValue("B{$row}", 'Valor');
                $this->headerRow($sheet, $row, 'A', 'B');
                $row++;

                foreach ($kpis as [$label, $value, $isCurrency]) {
                    $sheet->setCellValue("A{$row}", $label);
                    if ($isCurrency) {
                        $sheet->setCellValue("B{$row}", (float) $value);
                        $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00');
                    } else {
                        $sheet->setCellValue("B{$row}", (int) $value);
                    }
                    $this->dataRow($sheet, $row, 'A', 'B');
                    $row++;
                }
                $row++;

                // ── Appointments by status ───────────────────────────────────
                $appts = $this->payload['appointmentsByStatus'] ?? [];
                if (! empty($appts)) {
                    $row = $this->sectionHeader($sheet, $row, 'CITAS POR ESTADO', '0C4A6E', 'E0F2FE');
                    $sheet->setCellValue("A{$row}", 'Estado');
                    $sheet->setCellValue("B{$row}", 'Total');
                    $sheet->setCellValue("C{$row}", '% del total');
                    $this->headerRow($sheet, $row, 'A', 'C');
                    $row++;
                    $total = max(1, array_sum(array_column($appts, 'total')));
                    foreach ($appts as $r) {
                        $pct = round(($r['total'] / $total) * 100, 1);
                        $sheet->setCellValue("A{$row}", $this->statusLabels[$r['status']] ?? $r['status']);
                        $sheet->setCellValue("B{$row}", (int) $r['total']);
                        $sheet->setCellValue("C{$row}", "{$pct}%");
                        $this->dataRow($sheet, $row, 'A', 'C');
                        $row++;
                    }
                    $row++;
                }

                // ── Payments by status ───────────────────────────────────────
                $payStatus = $this->payload['paymentsSummary']['by_status'] ?? [];
                if (! empty($payStatus)) {
                    $row = $this->sectionHeader($sheet, $row, 'PAGOS POR ESTADO', '064E3B', 'D1FAE5');
                    $sheet->setCellValue("A{$row}", 'Estado');
                    $sheet->setCellValue("B{$row}", 'Cantidad');
                    $sheet->setCellValue("C{$row}", 'Monto');
                    $this->headerRow($sheet, $row, 'A', 'C');
                    $row++;
                    foreach ($payStatus as $r) {
                        $sheet->setCellValue("A{$row}", $this->statusLabels[$r['status']] ?? $r['status']);
                        $sheet->setCellValue("B{$row}", (int) $r['total']);
                        $sheet->setCellValue("C{$row}", (float) ($r['amount'] ?? 0));
                        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00');
                        $this->dataRow($sheet, $row, 'A', 'C');
                        $row++;
                    }
                    $row++;
                }

                // ── Activities by status ─────────────────────────────────────
                $actStatus = $this->payload['activitiesSummary']['by_status'] ?? [];
                if (! empty($actStatus)) {
                    $row = $this->sectionHeader($sheet, $row, 'ACTIVIDADES POR ESTADO', '3730A3', 'EDE9FE');
                    $sheet->setCellValue("A{$row}", 'Estado');
                    $sheet->setCellValue("B{$row}", 'Total');
                    $this->headerRow($sheet, $row, 'A', 'B');
                    $row++;
                    foreach ($actStatus as $r) {
                        $sheet->setCellValue("A{$row}", $this->statusLabels[$r['status']] ?? $r['status']);
                        $sheet->setCellValue("B{$row}", (int) $r['total']);
                        $this->dataRow($sheet, $row, 'A', 'B');
                        $row++;
                    }
                    $row++;
                }

                // ── Therapist productivity ───────────────────────────────────
                $productivity = $this->payload['therapistProductivity'] ?? [];
                if (! empty($productivity)) {
                    $row = $this->sectionHeader($sheet, $row, 'PRODUCTIVIDAD POR TERAPEUTA', '7C2D12', 'FEF3C7');
                    $sheet->setCellValue("A{$row}", 'Terapeuta');
                    $sheet->setCellValue("B{$row}", 'Sesiones');
                    $this->headerRow($sheet, $row, 'A', 'B');
                    $row++;
                    $maxSessions = max(1, max(array_column($productivity, 'total')));
                    foreach ($productivity as $r) {
                        $sheet->setCellValue("A{$row}", $r['therapist_name'] ?? 'Sin nombre');
                        $sheet->setCellValue("B{$row}", (int) $r['total']);
                        $this->dataRow($sheet, $row, 'A', 'B');
                        $row++;
                    }
                    $row++;
                }

                // ── Footer ───────────────────────────────────────────────────
                $sheet->mergeCells("A{$row}:F{$row}");
                $sheet->setCellValue("A{$row}", 'Generado por FisioVida · ' . now()->format('Y'));
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font'      => ['size' => 8, 'italic' => true, 'color' => ['rgb' => '94A3B8']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ── Column widths ────────────────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(38);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(16);
                $sheet->getColumnDimension('D')->setWidth(14);
                $sheet->getColumnDimension('E')->setWidth(14);
                $sheet->getColumnDimension('F')->setWidth(14);

                // ── Default font ─────────────────────────────────────────────
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
            },
        ];
    }

    private function sectionHeader($sheet, int $row, string $text, string $fgRgb, string $bgRgb): int
    {
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", $text);
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $fgRgb]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgRgb]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(20);

        return $row + 1;
    }

    private function headerRow($sheet, int $row, string $colFrom, string $colTo): void
    {
        $range = "{$colFrom}{$row}:{$colTo}{$row}";
        $sheet->getStyle($range)->applyFromArray([
            'font'    => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '1E293B']],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    private function dataRow($sheet, int $row, string $colFrom, string $colTo): void
    {
        $range = "{$colFrom}{$row}:{$colTo}{$row}";
        $even = $row % 2 === 0;
        $sheet->getStyle($range)->applyFromArray([
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $even ? 'F8FAFC' : 'FFFFFF']],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(17);
    }
}
