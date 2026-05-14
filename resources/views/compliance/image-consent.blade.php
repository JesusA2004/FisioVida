<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Consentimiento de Imágenes — {{ $patientName }}</title>
<style>
    @page { size: letter portrait; margin: 18mm 20mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; color: #1a1a2e; background: #fff; line-height: 1.55; }

    .print-btn { position: fixed; top: 12px; right: 16px; background: #d97706; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-size: 13px; cursor: pointer; z-index: 999; }
    @media print { .print-btn { display: none; } }

    .doc-header { border-bottom: 2px solid #d97706; padding-bottom: 10px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: flex-end; }
    .clinic-name { font-size: 18px; font-weight: bold; color: #d97706; }
    .clinic-info { font-size: 9px; color: #64748b; margin-top: 2px; }
    .doc-title { font-size: 13px; font-weight: bold; color: #1e293b; text-align: right; }

    h2 { font-size: 13px; font-weight: bold; color: #1e293b; text-align: center; margin: 10px 0 12px; text-transform: uppercase; letter-spacing: 0.04em; }
    h3 { font-size: 10px; font-weight: bold; color: #d97706; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin: 12px 0 6px; }
    p { margin-bottom: 7px; }
    ul { margin-left: 16px; margin-bottom: 7px; }

    .check-table { width: 100%; border-collapse: collapse; margin: 8px 0; }
    .check-table td { padding: 6px 10px; border: 1px solid #e2e8f0; font-size: 10px; }
    .check-table th { padding: 6px 10px; background: #fef3c7; border: 1px solid #e2e8f0; font-size: 9px; text-align: center; }
    .check-box { width: 20px; height: 20px; border: 1.5px solid #94a3b8; border-radius: 3px; display: inline-block; margin-right: 4px; vertical-align: middle; }

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
        <div class="doc-title">Autorización de Uso de Imágenes</div>
    </div>
</div>

<h2>Consentimiento para Uso de Imágenes y Material Visual</h2>

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

<h3>Objeto del consentimiento</h3>
<p>
    El suscrito autoriza a <strong>{{ $clinic['clinic_name'] }}</strong> para capturar fotografías, videos o imágenes
    relacionadas con su proceso de tratamiento fisioterapéutico con las finalidades señaladas a continuación.
</p>

<h3>Autorizaciones específicas</h3>
<table class="check-table">
    <thead>
        <tr>
            <th style="text-align:left;">Finalidad</th>
            <th style="width:80px;">Autoriza</th>
            <th style="width:80px;">No autoriza</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Seguimiento clínico en expediente electrónico (uso interno)</td>
            <td style="text-align:center;"><span class="check-box"></span></td>
            <td style="text-align:center;"><span class="check-box"></span></td>
        </tr>
        <tr>
            <td>Comparativos de evolución del tratamiento (uso interno)</td>
            <td style="text-align:center;"><span class="check-box"></span></td>
            <td style="text-align:center;"><span class="check-box"></span></td>
        </tr>
        <tr>
            <td>Capacitación interna de personal clínico (anonimizado)</td>
            <td style="text-align:center;"><span class="check-box"></span></td>
            <td style="text-align:center;"><span class="check-box"></span></td>
        </tr>
        <tr>
            <td>Publicación en redes sociales / material de difusión (con identidad protegida)</td>
            <td style="text-align:center;"><span class="check-box"></span></td>
            <td style="text-align:center;"><span class="check-box"></span></td>
        </tr>
        <tr>
            <td>Publicación en redes sociales / material de difusión (con identidad visible)</td>
            <td style="text-align:center;"><span class="check-box"></span></td>
            <td style="text-align:center;"><span class="check-box"></span></td>
        </tr>
        <tr>
            <td>Presentaciones académicas o científicas (anonimizado)</td>
            <td style="text-align:center;"><span class="check-box"></span></td>
            <td style="text-align:center;"><span class="check-box"></span></td>
        </tr>
    </tbody>
</table>

<h3>Compromisos del responsable</h3>
<ul>
    <li>Las imágenes no serán comercializadas ni cedidas a terceros sin consentimiento adicional.</li>
    <li>Serán almacenadas de forma segura y protegidas contra acceso no autorizado.</li>
    <li>El paciente puede revocar esta autorización en cualquier momento por escrito.</li>
</ul>

<h3>Vigencia y revocación</h3>
<p>
    Esta autorización tendrá vigencia indefinida salvo que el paciente la revoque expresamente.
    La revocación no afectará el material ya utilizado antes de la notificación.
</p>

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
            Firma del responsable del establecimiento<br>
            <span style="font-size:8px;">Nombre: __________________________________</span>
        </td>
    </tr>
</table>

<div class="footer">
    {{ $clinic['clinic_name'] }} &nbsp;·&nbsp; Consentimiento de uso de imágenes &nbsp;·&nbsp; Documento confidencial
</div>

</body>
</html>
