<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FisioVidaReportExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly array $payload)
    {
    }

    public function sheets(): array
    {
        return [
            new FisioVidaResumenSheet($this->payload),
            new FisioVidaCitasSheet($this->payload),
            new FisioVidaPagosSheet($this->payload),
            new FisioVidaActividadesSheet($this->payload),
            new FisioVidaProductividadSheet($this->payload),
        ];
    }
}

class FisioVidaResumenSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    public function __construct(private readonly array $payload)
    {
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function headings(): array
    {
        return ['Indicador', 'Valor'];
    }

    public function array(): array
    {
        $s = $this->payload['summary'] ?? [];
        $filters = $this->payload['filters'] ?? [];

        return [
            ['Periodo', ($filters['start_date'] ?? '') . ' al ' . ($filters['end_date'] ?? '')],
            ['Citas del periodo', $s['appointments_period'] ?? 0],
            ['Sesiones del periodo', $s['sessions_period'] ?? 0],
            ['Pacientes activos', $s['active_patients'] ?? 0],
            ['Pacientes nuevos', $s['new_patients_period'] ?? 0],
            ['Ingresos del periodo', '$' . number_format($s['income_period'] ?? 0, 2)],
            ['Pagos pendientes', '$' . number_format($s['pending_amount'] ?? 0, 2)],
            ['Total pagos registrados', $s['total_payments'] ?? 0],
            ['Actividades vencidas', $s['activities_overdue'] ?? 0],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

class FisioVidaCitasSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    public function __construct(private readonly array $payload)
    {
    }

    public function title(): string
    {
        return 'Citas por estado';
    }

    public function headings(): array
    {
        return ['Estado', 'Total'];
    }

    public function array(): array
    {
        return array_map(
            fn ($row) => [$row['status'], $row['total']],
            $this->payload['appointmentsByStatus'] ?? [],
        );
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}

class FisioVidaPagosSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    public function __construct(private readonly array $payload)
    {
    }

    public function title(): string
    {
        return 'Pagos por estado';
    }

    public function headings(): array
    {
        return ['Estado', 'Total', 'Monto'];
    }

    public function array(): array
    {
        return array_map(
            fn ($row) => [$row['status'], $row['total'], '$' . number_format($row['amount'] ?? 0, 2)],
            $this->payload['paymentsSummary']['by_status'] ?? [],
        );
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}

class FisioVidaActividadesSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    public function __construct(private readonly array $payload)
    {
    }

    public function title(): string
    {
        return 'Actividades por estado';
    }

    public function headings(): array
    {
        return ['Estado', 'Total'];
    }

    public function array(): array
    {
        return array_map(
            fn ($row) => [$row['status'], $row['total']],
            $this->payload['activitiesSummary']['by_status'] ?? [],
        );
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}

class FisioVidaProductividadSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    public function __construct(private readonly array $payload)
    {
    }

    public function title(): string
    {
        return 'Productividad terapeutas';
    }

    public function headings(): array
    {
        return ['Terapeuta', 'Sesiones'];
    }

    public function array(): array
    {
        return array_map(
            fn ($row) => [$row['therapist_name'], $row['total']],
            $this->payload['therapistProductivity'] ?? [],
        );
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
