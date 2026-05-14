<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Consentimiento Datos Sensibles — {{ $patientName }}</title>
<style>
    @page { size: letter portrait; margin: 18mm 20mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; color: #1a1a2e; background: #fff; line-height: 1.55; }

    .print-btn { position: fixed; top: 12px; right: 16px; background: #7c3aed; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-size: 13px; cursor: pointer; z-index: 999; }
    @media print { .print-btn { display: none; } }

    .doc-header { border-bottom: 2px solid #7c3aed; padding-bottom: 10px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: flex-end; }
    .clinic-name { font-size: 18px; font-weight: bold; color: #7c3aed; }
    .clinic-info { font-size: 9px; color: #64748b; margin-top: 2px; }
    .doc-title { font-size: 13px; font-weight: bold; color: #1e293b; text-align: right; }

    h2 { font-size: 13px; font-weight: bold; color: #1e293b; text-align: center; margin: 10px 0 12px; text-transform: uppercase; letter-spacing: 0.04em; }
    h3 { font-size: 10px; font-weight: bold; color: #7c3aed; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin: 12px 0 6px; }
    p { margin-bottom: 7px; }
    ul { margin-left: 16px; margin-bottom: 7px; }

    .highlight-box { background: #f5f3ff; border-left: 3px solid #7c3aed; padding: 8px 12px; margin: 8px 0; border-radius: 0 4px 4px 0; }

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
        <div class="doc-title">Consentimiento — Datos Sensibles de Salud</div>
    </div>
</div>

<h2>Consentimiento para el Tratamiento de Datos Personales Sensibles</h2>

<h3>Datos del paciente</h3>
<table class="patient-row">
    <tr>
        <td class="patient-cell" style="width:55%">
            <span class="field-label">Nombre completo</span>
            <div class="field-line">{{ $patientName ?: '' }}</div>
        </td>
        <td class="patient-cell" style="width:20%">
            <span class="field-label">Fecha de nacimiento</span>
            <div class="field-line">{{ $patient->fecha_nacimiento ?? '' }}</div>
        </td>
        <td class="patient-cell" style="width:25%">
            <span class="field-label">Expediente No.</span>
            <div class="field-line">{{ $patient->id ?? '' }}</div>
        </td>
    </tr>
</table>

<h3>Fundamento legal</h3>
<p>
    Conforme al Artículo 9 de la <em>Ley Federal de Protección de Datos Personales en Posesión de los Particulares</em> (LFPDPPP),
    el tratamiento de datos personales sensibles —como el estado de salud, diagnósticos, historiales clínicos y antecedentes médicos—
    requiere el consentimiento expreso y por escrito del titular.
</p>

<h3>Datos sensibles que se tratan</h3>
<div class="highlight-box">
    Los siguientes datos sensibles de salud serán tratados por <strong>{{ $clinic['clinic_name'] }}</strong>:
</div>
<ul>
    <li>Diagnóstico clínico, patologías y condiciones de salud actuales y pasadas.</li>
    <li>Historial de lesiones, cirugías y procedimientos médicos previos.</li>
    <li>Notas clínicas SOAP (subjetivo, objetivo, evaluación y plan de tratamiento).</li>
    <li>Registros de evolución y seguimiento fisioterapéutico.</li>
    <li>Resultados de estudios y exámenes clínicos (laboratorio, imagen, etc.).</li>
    <li>Alergias, medicamentos actuales y contraindicaciones.</li>
    <li>Fotografías e imágenes con fines clínicos.</li>
</ul>

<h3>Finalidad del tratamiento</h3>
<ul>
    <li><strong>Asistencial:</strong> diagnóstico, tratamiento, seguimiento y rehabilitación fisioterapéutica.</li>
    <li><strong>Administrativa:</strong> gestión de citas, pagos, expediente clínico y comunicación con el paciente.</li>
    <li><strong>Legal:</strong> cumplimiento de obligaciones ante autoridades sanitarias (NOM-004-SSA3-2012).</li>
</ul>

<h3>Confidencialidad y seguridad</h3>
<p>
    <strong>{{ $clinic['clinic_name'] }}</strong> garantiza que sus datos sensibles de salud serán tratados con estricta confidencialidad,
    almacenados de forma segura y accesibles únicamente por el personal autorizado involucrado en su atención.
    No serán vendidos, divulgados ni transferidos a terceros sin fundamento legal.
</p>

<h3>Conservación</h3>
<p>
    Su expediente clínico y datos sensibles se conservarán por un mínimo de <strong>5 años</strong> contados desde la última consulta,
    conforme a lo establecido en la NOM-004-SSA3-2012.
</p>

<h3>Derechos del titular</h3>
<p>
    Usted puede ejercer en cualquier momento sus derechos de Acceso, Rectificación, Cancelación y Oposición (ARCO)
    ante <strong>{{ $clinic['clinic_email'] ?: $clinic['clinic_name'] }}</strong>.
</p>

{{-- Declaración --}}
<div style="margin-top:12px; padding:10px 14px; border:1px solid #e2e8f0; border-radius:6px; background:#f8fafc;">
    <p>
        Habiendo sido informado/a claramente sobre el tipo de datos sensibles que serán tratados, las finalidades del tratamiento,
        las medidas de seguridad aplicadas y mis derechos como titular, otorgo mi <strong>consentimiento expreso y por escrito</strong>
        para que <strong>{{ $clinic['clinic_name'] }}</strong> trate mis datos personales sensibles de salud de conformidad
        con el Aviso de Privacidad y la LFPDPPP.
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
            Firma del responsable del tratamiento<br>
            <span style="font-size:8px;">Nombre: __________________________________</span>
        </td>
    </tr>
</table>

<div class="footer">
    {{ $clinic['clinic_name'] }} &nbsp;·&nbsp; Consentimiento de datos sensibles &nbsp;·&nbsp; LFPDPPP Art. 9 &nbsp;·&nbsp; NOM-004-SSA3-2012
</div>

</body>
</html>
