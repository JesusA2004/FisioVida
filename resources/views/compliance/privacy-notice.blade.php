<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Aviso de Privacidad — {{ $clinic['clinic_name'] }}</title>
<style>
    @page { size: letter portrait; margin: 18mm 20mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; color: #1a1a2e; background: #fff; line-height: 1.55; }

    .print-btn { position: fixed; top: 12px; right: 16px; background: #4f46e5; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-size: 13px; cursor: pointer; z-index: 999; }
    @media print { .print-btn { display: none; } }

    .doc-header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: flex-end; }
    .clinic-name { font-size: 18px; font-weight: bold; color: #4f46e5; }
    .clinic-info { font-size: 9px; color: #64748b; margin-top: 2px; }
    .doc-title { font-size: 14px; font-weight: bold; color: #1e293b; text-align: right; }

    h2 { font-size: 13px; font-weight: bold; color: #1e293b; text-align: center; margin: 10px 0 12px; text-transform: uppercase; letter-spacing: 0.04em; }
    h3 { font-size: 10px; font-weight: bold; color: #4f46e5; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin: 12px 0 6px; }
    p { margin-bottom: 7px; }

    .highlight-box { background: #f0f4ff; border-left: 3px solid #4f46e5; padding: 8px 12px; margin: 8px 0; border-radius: 0 4px 4px 0; }

    .sign-area { display: table; width: 100%; margin-top: 24px; }
    .sign-cell { display: table-cell; width: 48%; text-align: center; border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 9px; color: #475569; }
    .sign-spacer { display: table-cell; width: 4%; }
    .field-line { border-bottom: 1px solid #94a3b8; min-height: 18px; padding-bottom: 2px; margin: 4px 0; width: 100%; }

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
        <div class="doc-title">Aviso de Privacidad</div>
    </div>
</div>

<h2>Aviso de Privacidad Integral</h2>

<h3>Responsable del tratamiento</h3>
<p>
    <strong>{{ $clinic['clinic_name'] }}</strong> (en adelante "el Responsable"), con domicilio en
    <strong>{{ $clinic['clinic_address'] ?: '________________________________' }}</strong>,
    correo electrónico <strong>{{ $clinic['clinic_email'] ?: '________________________________' }}</strong>,
    teléfono <strong>{{ $clinic['clinic_phone'] ?: '________________________________' }}</strong>,
    es responsable del tratamiento de sus datos personales conforme a lo establecido en la
    <em>Ley Federal de Protección de Datos Personales en Posesión de los Particulares</em> (LFPDPPP).
</p>

<h3>Datos personales que se recaban</h3>
<p>Para llevar a cabo las finalidades descritas, recabaremos los siguientes datos personales:</p>
<ul style="margin-left:16px; margin-bottom:7px;">
    <li><strong>Datos de identificación:</strong> nombre completo, fecha de nacimiento, sexo, fotografía.</li>
    <li><strong>Datos de contacto:</strong> domicilio, teléfono, correo electrónico.</li>
    <li><strong>Datos de salud (sensibles):</strong> diagnósticos, historial clínico, tratamientos, evolución fisioterapéutica, imágenes clínicas.</li>
</ul>

<h3>Finalidades del tratamiento</h3>
<p><strong>Finalidades primarias (necesarias para la relación clínica):</strong></p>
<ul style="margin-left:16px; margin-bottom:7px;">
    <li>Brindar atención fisioterapéutica y seguimiento clínico.</li>
    <li>Elaborar y mantener su expediente clínico electrónico conforme a la NOM-004-SSA3-2012.</li>
    <li>Programar citas y sesiones de tratamiento.</li>
    <li>Emitir documentación de pago y recibos.</li>
    <li>Gestionar la seguridad e integridad del expediente.</li>
</ul>
<p><strong>Finalidades secundarias (opcionales):</strong></p>
<ul style="margin-left:16px; margin-bottom:7px;">
    <li>Envío de recordatorios de citas por correo o mensaje.</li>
    <li>Encuestas de satisfacción del servicio.</li>
</ul>

<h3>Transferencia de datos</h3>
<p>Sus datos no serán transferidos a terceros sin su consentimiento expreso, salvo en los casos previstos por la ley (autoridades de salud, requerimientos judiciales o legales).</p>

<h3>Derechos ARCO</h3>
<div class="highlight-box">
    Usted tiene derecho a <strong>Acceder</strong>, <strong>Rectificar</strong>, <strong>Cancelar</strong> u <strong>Oponerse</strong> al tratamiento de sus datos personales (derechos ARCO).
    Para ejercerlos, diríjase a: <strong>{{ $clinic['clinic_email'] ?: '________________________________' }}</strong>.
    Su solicitud será atendida en un plazo de 20 días hábiles.
</div>

<h3>Conservación del expediente</h3>
<p>Conforme a la NOM-004-SSA3-2012, su expediente clínico se conservará por un mínimo de <strong>5 años</strong> a partir de su última consulta.</p>

<h3>Cambios al aviso de privacidad</h3>
<p>Cualquier modificación a este aviso estará disponible en nuestras instalaciones y, en su caso, será notificada por correo electrónico. La versión vigente es la que se entrega al paciente al momento del registro.</p>

{{-- Aceptación --}}
<div style="margin-top:18px; padding:10px 14px; border:1px solid #e2e8f0; border-radius:6px; background:#f8fafc;">
    <p style="margin-bottom:8px;"><strong>Aceptación del aviso de privacidad</strong></p>
    <p>
        Yo, <span class="field-line" style="display:inline-block; width:260px;">{{ $patientName ?: '' }}</span>,
        declaro haber leído y entendido el presente Aviso de Privacidad y otorgo mi consentimiento para el tratamiento de mis datos personales, incluyendo mis datos sensibles de salud, para las finalidades aquí descritas.
    </p>
</div>

<table class="sign-area">
    <tr>
        <td class="sign-cell">
            <div style="min-height:40px;"></div>
            Firma del paciente / tutor<br>
            <span style="font-size:8px;">Nombre: __________________________________</span>
        </td>
        <td class="sign-spacer"></td>
        <td class="sign-cell">
            Fecha: ______ / ______ / ____________<br>
            Versión: 1.0 &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
        </td>
    </tr>
</table>

<div class="footer">
    {{ $clinic['clinic_name'] }} &nbsp;·&nbsp; Aviso de Privacidad &nbsp;·&nbsp; LFPDPPP &nbsp;·&nbsp; NOM-004-SSA3-2012
</div>

</body>
</html>
