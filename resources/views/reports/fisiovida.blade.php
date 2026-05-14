<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte FisioVida</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1a1a2e; background: #fff; padding: 24px 28px; }

        /* Header */
        .report-header { background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); border-radius: 10px; padding: 18px 22px; margin-bottom: 18px; }
        .report-header h1 { font-size: 22px; font-weight: bold; color: #fff; letter-spacing: 0.02em; }
        .report-header .subtitle { font-size: 11px; color: #c7d2fe; margin-top: 3px; }
        .report-header .meta { font-size: 9px; color: #a5b4fc; margin-top: 6px; }

        /* Section title */
        h2 { font-size: 11px; font-weight: bold; color: #1e293b; margin: 16px 0 6px;
             border-left: 3px solid #4f46e5; padding-left: 7px; text-transform: uppercase; letter-spacing: 0.05em; }

        /* KPI grid */
        .kpi-grid { display: table; width: 100%; border-spacing: 5px; border-collapse: separate; margin-bottom: 4px; }
        .kpi-row { display: table-row; }
        .kpi-cell { display: table-cell; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 7px; padding: 10px 14px; width: 25%; vertical-align: top; }
        .kpi-label { font-size: 8px; text-transform: uppercase; color: #64748b; letter-spacing: 0.06em; }
        .kpi-value { font-size: 20px; font-weight: bold; color: #0f172a; margin-top: 3px; }
        .kpi-value.green { color: #059669; }
        .kpi-value.amber { color: #d97706; }
        .kpi-value.red   { color: #dc2626; }

        /* Section card */
        .section-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 10px; }

        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; text-align: left; padding: 5px 10px; font-size: 9px; color: #475569; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 1px solid #cbd5e1; }
        td { padding: 5px 10px; border-bottom: 1px solid #f1f5f9; font-size: 10px; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:nth-child(even) td { background: #f8fafc; }

        /* Horizontal bar chart */
        .bar-wrap { display: table; width: 100%; }
        .bar-row { display: table-row; height: 28px; }
        .bar-label { display: table-cell; width: 22%; vertical-align: middle; font-size: 9px; color: #374151; padding-right: 8px; white-space: nowrap; }
        .bar-track-cell { display: table-cell; vertical-align: middle; padding: 4px 0; }
        .bar-track { background: #e2e8f0; border-radius: 4px; height: 14px; position: relative; }
        .bar-fill { height: 14px; border-radius: 4px; }
        .bar-pct { display: table-cell; width: 40px; vertical-align: middle; font-size: 9px; font-weight: bold; color: #374151; padding-left: 6px; text-align: right; }

        /* Colors */
        .fill-blue   { background: #6366f1; }
        .fill-green  { background: #10b981; }
        .fill-amber  { background: #f59e0b; }
        .fill-rose   { background: #f43f5e; }
        .fill-sky    { background: #0ea5e9; }
        .fill-violet { background: #8b5cf6; }
        .fill-zinc   { background: #71717a; }

        /* Status badge */
        .badge { display: inline-block; padding: 1px 7px; border-radius: 99px; font-size: 8px; font-weight: bold; }
        .badge-scheduled  { background: #e0f2fe; color: #0369a1; }
        .badge-confirmed  { background: #d1fae5; color: #065f46; }
        .badge-done       { background: #ede9fe; color: #5b21b6; }
        .badge-cancelled  { background: #f1f5f9; color: #475569; }
        .badge-no_show    { background: #ffe4e6; color: #9f1239; }
        .badge-pending    { background: #fef3c7; color: #92400e; }
        .badge-paid       { background: #d1fae5; color: #065f46; }
        .badge-partial    { background: #e0f2fe; color: #0369a1; }
        .badge-refunded   { background: #ede9fe; color: #5b21b6; }
        .badge-in_progress { background: #e0f2fe; color: #0369a1; }
        .badge-on_hold    { background: #f1f5f9; color: #475569; }
        .badge-completed  { background: #d1fae5; color: #065f46; }
        .badge-overdue    { background: #ffe4e6; color: #9f1239; }

        /* Footer */
        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #94a3b8; text-align: center; }

        /* Two-column layout */
        .two-col { display: table; width: 100%; border-spacing: 8px; }
        .col { display: table-cell; vertical-align: top; width: 50%; }
    </style>
</head>
<body>

{{-- ── Header ─────────────────────────────────────────────────────────────── --}}
<div class="report-header">
    <h1>{{ $clinicName }}</h1>
    <div class="subtitle">Reporte ejecutivo · Generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }}</div>
    <div class="meta">Periodo: {{ $filters['start_date'] }} al {{ $filters['end_date'] }}</div>
</div>

{{-- ── KPIs ────────────────────────────────────────────────────────────────── --}}
<h2>Resumen general</h2>
<table class="kpi-grid">
    <tr class="kpi-row">
        <td class="kpi-cell">
            <div class="kpi-label">Citas del periodo</div>
            <div class="kpi-value">{{ $summary['appointments_period'] ?? 0 }}</div>
        </td>
        <td class="kpi-cell">
            <div class="kpi-label">Sesiones del periodo</div>
            <div class="kpi-value">{{ $summary['sessions_period'] ?? 0 }}</div>
        </td>
        <td class="kpi-cell">
            <div class="kpi-label">Pacientes activos</div>
            <div class="kpi-value">{{ $summary['active_patients'] ?? 0 }}</div>
        </td>
        <td class="kpi-cell">
            <div class="kpi-label">Pacientes nuevos</div>
            <div class="kpi-value">{{ $summary['new_patients_period'] ?? 0 }}</div>
        </td>
    </tr>
    <tr class="kpi-row">
        <td class="kpi-cell">
            <div class="kpi-label">Ingresos del periodo</div>
            <div class="kpi-value green">${{ number_format($summary['income_period'] ?? 0, 2) }}</div>
        </td>
        <td class="kpi-cell">
            <div class="kpi-label">Pagos pendientes (monto)</div>
            <div class="kpi-value amber">${{ number_format($summary['pending_amount'] ?? 0, 2) }}</div>
        </td>
        <td class="kpi-cell">
            <div class="kpi-label">Total pagos registrados</div>
            <div class="kpi-value">{{ $summary['total_payments'] ?? 0 }}</div>
        </td>
        <td class="kpi-cell">
            <div class="kpi-label">Actividades vencidas</div>
            <div class="kpi-value {{ ($summary['activities_overdue'] ?? 0) > 0 ? 'red' : '' }}">{{ $summary['activities_overdue'] ?? 0 }}</div>
        </td>
    </tr>
</table>

@php
    $statusLabels = [
        'scheduled' => 'Programada', 'confirmed' => 'Confirmada', 'done' => 'Realizada',
        'cancelled' => 'Cancelada', 'no_show' => 'No asistió',
        'pending' => 'Pendiente', 'paid' => 'Pagado', 'partial' => 'Parcial', 'refunded' => 'Reembolsado',
        'in_progress' => 'En progreso', 'on_hold' => 'En espera', 'completed' => 'Completada', 'overdue' => 'Vencida',
    ];
    $fillColors = [
        'scheduled' => 'fill-sky', 'confirmed' => 'fill-green', 'done' => 'fill-violet',
        'cancelled' => 'fill-zinc', 'no_show' => 'fill-rose',
        'pending' => 'fill-amber', 'paid' => 'fill-green', 'partial' => 'fill-sky', 'refunded' => 'fill-violet',
        'in_progress' => 'fill-sky', 'on_hold' => 'fill-zinc', 'completed' => 'fill-green', 'overdue' => 'fill-rose',
    ];
@endphp

{{-- ── Two-col: Citas + Pagos ─────────────────────────────────────────────── --}}
<table class="two-col">
    <tr>
        {{-- Citas por estado --}}
        @if(count($appointmentsByStatus))
        <td class="col">
            <h2>Citas por estado</h2>
            <div class="section-card">
                @php $maxAppt = max(1, max(array_column($appointmentsByStatus, 'total'))); @endphp
                <div class="bar-wrap">
                    @foreach($appointmentsByStatus as $row)
                    <div class="bar-row">
                        <div class="bar-label">
                            <span class="badge badge-{{ $row['status'] }}">{{ $statusLabels[$row['status']] ?? $row['status'] }}</span>
                        </div>
                        <div class="bar-track-cell">
                            <div class="bar-track">
                                <div class="bar-fill {{ $fillColors[$row['status']] ?? 'fill-blue' }}" style="width:{{ min(100, round(($row['total']/$maxAppt)*100)) }}%;"></div>
                            </div>
                        </div>
                        <div class="bar-pct">{{ $row['total'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </td>
        @endif

        {{-- Pagos por estado --}}
        @if(count($paymentsSummary['by_status'] ?? []))
        <td class="col">
            <h2>Pagos por estado</h2>
            <div class="section-card">
                @php $maxPay = max(1, max(array_column($paymentsSummary['by_status'], 'total'))); @endphp
                <div class="bar-wrap">
                    @foreach($paymentsSummary['by_status'] as $row)
                    <div class="bar-row">
                        <div class="bar-label">
                            <span class="badge badge-{{ $row['status'] }}">{{ $statusLabels[$row['status']] ?? $row['status'] }}</span>
                        </div>
                        <div class="bar-track-cell">
                            <div class="bar-track">
                                <div class="bar-fill {{ $fillColors[$row['status']] ?? 'fill-blue' }}" style="width:{{ min(100, round(($row['total']/$maxPay)*100)) }}%;"></div>
                            </div>
                        </div>
                        <div class="bar-pct">{{ $row['total'] }}</div>
                    </div>
                    @endforeach
                </div>
                @php $totalIncome = array_sum(array_column($paymentsSummary['by_status'], 'amount')); @endphp
                <div style="margin-top:6px; font-size:9px; color:#475569; text-align:right;">
                    Total monto: <strong>${{ number_format($totalIncome, 2) }}</strong>
                </div>
            </div>
        </td>
        @endif
    </tr>
</table>

{{-- ── Two-col: Actividades + Productividad ───────────────────────────────── --}}
<table class="two-col">
    <tr>
        {{-- Actividades por estado --}}
        @if(count($activitiesSummary['by_status'] ?? []))
        <td class="col">
            <h2>Actividades por estado</h2>
            <div class="section-card">
                @php $maxAct = max(1, max(array_column($activitiesSummary['by_status'], 'total'))); @endphp
                <div class="bar-wrap">
                    @foreach($activitiesSummary['by_status'] as $row)
                    <div class="bar-row">
                        <div class="bar-label">
                            <span class="badge badge-{{ $row['status'] }}">{{ $statusLabels[$row['status']] ?? $row['status'] }}</span>
                        </div>
                        <div class="bar-track-cell">
                            <div class="bar-track">
                                <div class="bar-fill {{ $fillColors[$row['status']] ?? 'fill-blue' }}" style="width:{{ min(100, round(($row['total']/$maxAct)*100)) }}%;"></div>
                            </div>
                        </div>
                        <div class="bar-pct">{{ $row['total'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </td>
        @endif

        {{-- Productividad por terapeuta --}}
        @if(count($therapistProductivity))
        <td class="col">
            <h2>Productividad por terapeuta</h2>
            <div class="section-card">
                @php $maxProd = max(1, max(array_column($therapistProductivity, 'total'))); @endphp
                <div class="bar-wrap">
                    @foreach($therapistProductivity as $row)
                    <div class="bar-row">
                        <div class="bar-label" style="font-size:8px;">{{ mb_strimwidth($row['therapist_name'] ?? 'Sin nombre', 0, 18, '…') }}</div>
                        <div class="bar-track-cell">
                            <div class="bar-track">
                                <div class="bar-fill fill-violet" style="width:{{ min(100, round(($row['total']/$maxProd)*100)) }}%;"></div>
                            </div>
                        </div>
                        <div class="bar-pct">{{ $row['total'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </td>
        @endif
    </tr>
</table>

<div class="footer">
    Generado por FisioVida &nbsp;·&nbsp; {{ now()->format('Y') }} &nbsp;·&nbsp; Documento confidencial de uso interno
</div>

</body>
</html>
