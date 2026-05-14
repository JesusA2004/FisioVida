<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte FisioVida</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; padding: 28px; }
        h1 { font-size: 20px; font-weight: bold; color: #0f172a; }
        h2 { font-size: 14px; font-weight: bold; color: #0f172a; margin: 18px 0 6px; border-bottom: 2px solid #e2e8f0; padding-bottom: 4px; }
        h3 { font-size: 12px; font-weight: bold; color: #334155; margin: 14px 0 4px; }
        .meta { font-size: 10px; color: #64748b; margin-top: 4px; }
        .kpi-grid { display: table; width: 100%; border-collapse: separate; border-spacing: 6px; margin: 10px 0; }
        .kpi-cell { display: table-cell; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; width: 25%; }
        .kpi-label { font-size: 9px; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; }
        .kpi-value { font-size: 18px; font-weight: bold; color: #0f172a; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        th { background: #f1f5f9; text-align: left; padding: 6px 10px; font-size: 10px; color: #475569; text-transform: uppercase; letter-spacing: 0.04em; }
        td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-size: 11px; }
        tr:last-child td { border-bottom: none; }
        .section-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin: 10px 0; }
        .badge-emerald { background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 99px; font-size: 10px; }
        .badge-amber { background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 99px; font-size: 10px; }
        .badge-rose { background: #ffe4e6; color: #9f1239; padding: 2px 8px; border-radius: 99px; font-size: 10px; }
        .footer { margin-top: 30px; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        .bar-bg { background: #e2e8f0; border-radius: 4px; height: 8px; margin-top: 4px; }
        .bar-fill { background: #3b82f6; border-radius: 4px; height: 8px; }
    </style>
</head>
<body>

<h1>{{ $clinicName }}</h1>
<p class="meta">
    Reporte generado el {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp;
    Periodo: {{ $filters['start_date'] }} al {{ $filters['end_date'] }}
</p>

<h2>Resumen general</h2>
<table class="kpi-grid">
    <tr>
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
    <tr>
        <td class="kpi-cell">
            <div class="kpi-label">Ingresos del periodo</div>
            <div class="kpi-value" style="font-size:14px; color:#065f46;">${{ number_format($summary['income_period'] ?? 0, 2) }}</div>
        </td>
        <td class="kpi-cell">
            <div class="kpi-label">Pagos pendientes</div>
            <div class="kpi-value" style="font-size:14px; color:#92400e;">${{ number_format($summary['pending_amount'] ?? 0, 2) }}</div>
        </td>
        <td class="kpi-cell">
            <div class="kpi-label">Total pagos registrados</div>
            <div class="kpi-value">{{ $summary['total_payments'] ?? 0 }}</div>
        </td>
        <td class="kpi-cell">
            <div class="kpi-label">Actividades vencidas</div>
            <div class="kpi-value" style="color:#9f1239;">{{ $summary['activities_overdue'] ?? 0 }}</div>
        </td>
    </tr>
</table>

@if(count($appointmentsByStatus))
<h2>Citas por estado</h2>
<div class="section-card">
    <table>
        <thead><tr><th>Estado</th><th>Total</th></tr></thead>
        <tbody>
            @foreach($appointmentsByStatus as $row)
            <tr>
                <td>{{ $row['status'] }}</td>
                <td><strong>{{ $row['total'] }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(count($paymentsSummary['by_status'] ?? []))
<h2>Pagos por estado</h2>
<div class="section-card">
    <table>
        <thead><tr><th>Estado</th><th>Cantidad</th><th>Monto</th></tr></thead>
        <tbody>
            @foreach($paymentsSummary['by_status'] as $row)
            <tr>
                <td>{{ $row['status'] }}</td>
                <td>{{ $row['total'] }}</td>
                <td><strong>${{ number_format($row['amount'] ?? 0, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(count($activitiesSummary['by_status'] ?? []))
<h2>Actividades por estado</h2>
<div class="section-card">
    <table>
        <thead><tr><th>Estado</th><th>Total</th></tr></thead>
        <tbody>
            @foreach($activitiesSummary['by_status'] as $row)
            <tr>
                <td>{{ $row['status'] }}</td>
                <td><strong>{{ $row['total'] }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(count($therapistProductivity))
<h2>Productividad por terapeuta</h2>
<div class="section-card">
    <table>
        <thead><tr><th>Terapeuta</th><th>Sesiones</th></tr></thead>
        <tbody>
            @php $maxTotal = max(array_column($therapistProductivity, 'total') + [1]); @endphp
            @foreach($therapistProductivity as $row)
            <tr>
                <td>{{ $row['therapist_name'] }}</td>
                <td>
                    <strong>{{ $row['total'] }}</strong>
                    <div class="bar-bg"><div class="bar-fill" style="width: {{ min(100, round(($row['total'] / $maxTotal) * 100)) }}%;"></div></div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="footer">
    Generado por FisioVida &nbsp;·&nbsp; {{ now()->format('Y') }}
</div>

</body>
</html>
