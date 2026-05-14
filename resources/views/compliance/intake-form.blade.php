<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ficha de Ingreso — {{ $patientName }}</title>
<style>
    @page { size: letter portrait; margin: 18mm 20mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; }

    .print-btn {
        position: fixed; top: 12px; right: 16px;
        background: #4f46e5; color: #fff; border: none; border-radius: 6px;
        padding: 8px 18px; font-size: 13px; cursor: pointer; z-index: 999;
    }
    @media print {
        .print-btn { display: none; }
        body { font-size: 10px; }
    }

    .doc-header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: flex-end; }
    .clinic-name { font-size: 18px; font-weight: bold; color: #4f46e5; }
    .clinic-info { font-size: 9px; color: #64748b; margin-top: 2px; }
    .doc-title { font-size: 14px; font-weight: bold; color: #1e293b; text-align: right; }
    .doc-date { font-size: 9px; color: #64748b; text-align: right; margin-top: 2px; }

    h3 { font-size: 10px; font-weight: bold; color: #4f46e5; text-transform: uppercase; letter-spacing: 0.06em;
         border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin: 14px 0 8px; }

    .field-grid { display: table; width: 100%; border-spacing: 0 5px; }
    .field-row { display: table-row; }
    .field-cell { display: table-cell; padding-right: 12px; vertical-align: bottom; }
    .field-cell:last-child { padding-right: 0; }
    .field-label { font-size: 9px; color: #64748b; margin-bottom: 2px; display: block; }
    .field-value { font-size: 11px; color: #0f172a; font-weight: 500; }
    .field-line { border-bottom: 1px solid #94a3b8; min-height: 18px; padding-bottom: 2px; margin-top: 1px; width: 100%; }

    .two-col { display: table; width: 100%; }
    .col-half { display: table-cell; width: 50%; padding-right: 10px; vertical-align: top; }
    .col-half:last-child { padding-right: 0; }

    .textarea-line { border-bottom: 1px solid #94a3b8; min-height: 14px; margin-bottom: 4px; }

    .sign-area { display: table; width: 100%; margin-top: 20px; }
    .sign-cell { display: table-cell; width: 48%; text-align: center; border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 9px; color: #475569; }
    .sign-cell + .sign-cell { margin-left: 4%; }
    .sign-spacer { display: table-cell; width: 4%; }

    .footer { margin-top: 16px; font-size: 8px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 6px; }
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
            @if($clinic['clinic_address'])<br>{{ $clinic['clinic_address'] }} @endif
        </div>
    </div>
    <div>
        <div class="doc-title">Ficha de Ingreso del Paciente</div>
        <div class="doc-date">Fecha: ______ / ______ / ____________</div>
    </div>
</div>

{{-- Datos personales --}}
<h3>Datos personales</h3>
<table class="field-grid">
    <tr class="field-row">
        <td class="field-cell" style="width:40%">
            <span class="field-label">Nombre completo</span>
            <div class="field-line">{{ $patientName ?: '' }}</div>
        </td>
        <td class="field-cell" style="width:20%">
            <span class="field-label">Fecha de nacimiento</span>
            <div class="field-line">{{ $patient->fecha_nacimiento ?? '' }}</div>
        </td>
        <td class="field-cell" style="width:12%">
            <span class="field-label">Edad</span>
            <div class="field-line"></div>
        </td>
        <td class="field-cell" style="width:14%">
            <span class="field-label">Sexo</span>
            <div class="field-line">{{ $patient->sexo ?? '' }}</div>
        </td>
    </tr>
    <tr class="field-row">
        <td class="field-cell" style="width:30%">
            <span class="field-label">Teléfono</span>
            <div class="field-line">{{ $patient->telefono ?? '' }}</div>
        </td>
        <td class="field-cell" style="width:40%">
            <span class="field-label">Correo electrónico</span>
            <div class="field-line">{{ $patient->email ?? '' }}</div>
        </td>
        <td class="field-cell" style="width:30%">
            <span class="field-label">Ocupación</span>
            <div class="field-line"></div>
        </td>
    </tr>
    <tr class="field-row">
        <td class="field-cell" colspan="3">
            <span class="field-label">Domicilio</span>
            <div class="field-line">{{ $patient->domicilio ?? '' }}</div>
        </td>
    </tr>
</table>

{{-- Contacto de emergencia --}}
<h3>Contacto de emergencia</h3>
<table class="field-grid">
    <tr class="field-row">
        <td class="field-cell" style="width:45%">
            <span class="field-label">Nombre del contacto</span>
            <div class="field-line"></div>
        </td>
        <td class="field-cell" style="width:25%">
            <span class="field-label">Parentesco</span>
            <div class="field-line"></div>
        </td>
        <td class="field-cell" style="width:30%">
            <span class="field-label">Teléfono de emergencia</span>
            <div class="field-line"></div>
        </td>
    </tr>
</table>

{{-- Motivo de consulta --}}
<h3>Motivo de consulta</h3>
<div class="textarea-line"></div>
<div class="textarea-line"></div>
<div class="textarea-line"></div>

{{-- Antecedentes clínicos --}}
<h3>Antecedentes clínicos</h3>
<table class="two-col">
    <tr>
        <td class="col-half">
            <span class="field-label">Alergias conocidas</span>
            <div class="textarea-line"></div>
            <div class="textarea-line"></div>
        </td>
        <td class="col-half">
            <span class="field-label">Medicamentos actuales</span>
            <div class="textarea-line"></div>
            <div class="textarea-line"></div>
        </td>
    </tr>
    <tr>
        <td class="col-half" style="padding-top:6px;">
            <span class="field-label">Lesiones / cirugías previas</span>
            <div class="textarea-line"></div>
            <div class="textarea-line"></div>
        </td>
        <td class="col-half" style="padding-top:6px;">
            <span class="field-label">Enfermedades crónicas / padecimientos</span>
            <div class="textarea-line"></div>
            <div class="textarea-line"></div>
        </td>
    </tr>
</table>

<h3>Observaciones adicionales</h3>
<div class="textarea-line"></div>
<div class="textarea-line"></div>

{{-- Firmas --}}
<table class="sign-area" style="margin-top:30px;">
    <tr>
        <td class="sign-cell">
            <div style="min-height:40px;"></div>
            Firma del paciente / tutor
        </td>
        <td class="sign-spacer"></td>
        <td class="sign-cell">
            <div style="min-height:40px;"></div>
            Firma del terapeuta responsable
        </td>
    </tr>
</table>

<div class="footer">
    {{ $clinic['clinic_name'] }} &nbsp;·&nbsp; Ficha de ingreso &nbsp;·&nbsp; Información confidencial
</div>

</body>
</html>
