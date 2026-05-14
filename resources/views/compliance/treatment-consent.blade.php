<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Consentimiento de Tratamiento — {{ $patientName }}</title>
<style>
    @page { size: letter portrait; margin: 18mm 20mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; color: #1a1a2e; background: #fff; line-height: 1.55; }

    .print-btn { position: fixed; top: 12px; right: 16px; background: #059669; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-size: 13px; cursor: pointer; z-index: 999; }
    @media print { .print-btn { display: none; } }

    .doc-header { border-bottom: 2px solid #059669; padding-bottom: 10px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: flex-end; }
    .clinic-name { font-size: 18px; font-weight: bold; color: #059669; }
    .clinic-info { font-size: 9px; color: #64748b; margin-top: 2px; }
    .doc-title { font-size: 13px; font-weight: bold; color: #1e293b; text-align: right; }

    h2 { font-size: 13px; font-weight: bold; color: #1e293b; text-align: center; margin: 10px 0 12px; text-transform: uppercase; letter-spacing: 0.04em; }
    h3 { font-size: 10px; font-weight: bold; color: #059669; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin: 12px 0 6px; }
    p { margin-bottom: 7px; }
    ul { margin-left: 16px; margin-bottom: 7px; }

    .highlight-box { background: #f0fdf4; border-left: 3px solid #059669; padding: 8px 12px; margin: 8px 0; border-radius: 0 4px 4px 0; }

    .patient-row { display: table; width: 100%; margin: 8px 0; }
    .patient-cell { display: table-cell; padding-right: 10px; vertical-align: bottom; }
    .field-label { font-size: 9px; color: #64748b; margin-bottom: 2px; display: block; }
    .field-line { border-bottom: 1px solid #94a3b8; min-height: 18px; padding-bottom: 2px; width: 100%; }

    .sign-area { display: table; width: 100%; margin-top: 24px; }
    .sign-cell { display: table-cell; width: 48%; text-align: center; border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 9px; color: #475569; }
    .sign-spacer { display: table-cell; width: 4%; }

    .footer { margin-top: 14px; font-size: 8px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 6px; }
</style>
</head>
<body>

<button class="print-btn" onclick="window.print()">Imprimir</button>

<div class="doc-header">
    <div>
        <div class="clinic-name">{{ $clinic['clinic_name'] }}</div>
        <div class="clinic-info">
            @if($clinic['clinic_phone']) Tel: {{ $clinic['clinic_phone'] }} &nbsp;·&nbsp; @endif
            @if($clinic['clinic_email']) {{ $clinic['clinic_email'] }} @endif
        </div>
    </div>
    <div>
        <div class="doc-title">Consentimiento Informado</div>
        <div style="font-size:9px; color:#64748b; text-align:right; margin-top:2px;">Tratamiento fisioterapéutico</div>
    </div>
</div>

<h2>Consentimiento Informado de Tratamiento Fisioterapéutico</h2>

<h3>Datos del paciente</h3>
<table class="patient-row">
    <tr>
        <td class="patient-cell" style="width:50%">
            <span class="field-label">Nombre completo del paciente</span>
            <div class="field-line">{{ $patientName ?: '' }}</div>
        </td>
        <td class="patient-cell" style="width:25%">
            <span class="field-label">Fecha de nacimiento</span>
            <div class="field-line">{{ $patient->fecha_nacimiento ?? '' }}</div>
        </td>
        <td class="patient-cell" style="width:25%">
            <span class="field-label">Expediente No.</span>
            <div class="field-line">{{ $patient->id ?? '' }}</div>
        </td>
    </tr>
</table>

<h3>Información general del tratamiento</h3>
<p>
    El terapeuta responsable me ha explicado en términos comprensibles la naturaleza del tratamiento fisioterapéutico propuesto,
    el cual puede incluir técnicas manuales, ejercicio terapéutico, electroterapia, termoterapia, crioterapia u otras modalidades
    acordes con mi diagnóstico.
</p>

<h3>Beneficios esperados</h3>
<ul>
    <li>Reducción del dolor y la inflamación.</li>
    <li>Mejora de la movilidad, fuerza y funcionalidad.</li>
    <li>Prevención de lesiones secundarias y recaídas.</li>
    <li>Mejora en la calidad de vida y capacidad de actividades cotidianas.</li>
</ul>

<h3>Posibles molestias, riesgos y efectos secundarios</h3>
<ul>
    <li>Dolor muscular temporal después de las sesiones (agujetas o sensibilidad).</li>
    <li>Enrojecimiento o sensación de calor en zonas tratadas.</li>
    <li>Mareo o fatiga en casos específicos de ejercicio intenso.</li>
    <li>En casos excepcionales: hematomas, inflamación temporal.</li>
</ul>
<p>He sido informado/a de que los riesgos graves son poco comunes y que el personal adoptará todas las medidas preventivas.</p>

<h3>Alternativas de tratamiento</h3>
<p>El terapeuta me ha informado sobre posibles alternativas de tratamiento y he tenido la oportunidad de realizar preguntas, las cuales han sido resueltas satisfactoriamente.</p>

<h3>Derecho a retractarse</h3>
<div class="highlight-box">
    Entiendo que puedo <strong>retirar mi consentimiento</strong> en cualquier momento sin que ello perjudique mi atención, notificándolo al terapeuta responsable.
</div>

<h3>Observaciones o condiciones especiales</h3>
<div style="border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px; min-height: 40px; margin-bottom: 8px; background: #f8fafc;"></div>

{{-- Declaración y firma --}}
<div style="margin-top:14px; padding:10px 14px; border:1px solid #e2e8f0; border-radius:6px; background:#f8fafc;">
    <p>
        Habiendo leído y comprendido la información anterior, y de manera <strong>libre y voluntaria</strong>, otorgo mi consentimiento para recibir el tratamiento fisioterapéutico en <strong>{{ $clinic['clinic_name'] }}</strong>.
    </p>
</div>

<table class="sign-area">
    <tr>
        <td class="sign-cell">
            <div style="min-height:40px;"></div>
            Firma del paciente / tutor legal<br>
            <span style="font-size:8px;">Nombre: __________________________________<br>Fecha: ______ / ______ / ____________</span>
        </td>
        <td class="sign-spacer"></td>
        <td class="sign-cell">
            <div style="min-height:40px;"></div>
            Firma y nombre del terapeuta responsable<br>
            <span style="font-size:8px;">Cédula profesional: ____________________</span>
        </td>
    </tr>
</table>

<div class="footer">
    {{ $clinic['clinic_name'] }} &nbsp;·&nbsp; Consentimiento informado de tratamiento &nbsp;·&nbsp; Documento confidencial
</div>

</body>
</html>
