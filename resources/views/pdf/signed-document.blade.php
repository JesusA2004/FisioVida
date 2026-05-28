<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 10pt;
        color: #222;
        line-height: 1.45;
    }
    .page { padding: 18mm 18mm 14mm 18mm; }

    .header-table { width: 100%; border-bottom: 2px solid #4f46e5; padding-bottom: 10pt; margin-bottom: 12pt; }
    .header-title { font-size: 15pt; font-weight: bold; color: #4f46e5; }
    .header-sub { font-size: 9pt; color: #555; margin-top: 3pt; }

    .section { margin-bottom: 12pt; }
    .section-title {
        font-size: 10pt; font-weight: bold; color: #4f46e5;
        border-bottom: 1px solid #c7d2fe; padding-bottom: 3pt; margin-bottom: 6pt;
    }

    table.data { width: 100%; border-collapse: collapse; }
    table.data td { padding: 3pt 4pt; vertical-align: top; font-size: 9pt; }
    table.data td.lbl { font-weight: bold; color: #555; width: 38%; }

    .hash-cell {
        background: #f3f4f6; border: 1px dashed #9ca3af;
        padding: 4pt; font-size: 7.5pt;
        font-family: 'Courier New', monospace; word-break: break-all;
    }

    .content-box {
        background: #f9fafb; border: 1px solid #e5e7eb;
        padding: 8pt; font-size: 8.5pt; white-space: pre-wrap;
        line-height: 1.4;
    }

    .sig-box {
        border: 1px solid #d1d5db; background: #fff;
        padding: 4pt; display: inline-block;
    }

    .disclaimer {
        background: #fffbeb; border: 1px solid #fbbf24;
        padding: 8pt; font-size: 8.5pt; color: #92400e;
        margin-top: 12pt;
    }
    .disclaimer strong { color: #78350f; }

    .footer {
        margin-top: 14pt; text-align: center;
        font-size: 8pt; color: #9ca3af;
        border-top: 1px solid #e5e7eb; padding-top: 6pt;
    }

    .badge {
        display: inline-block; padding: 2pt 7pt;
        border-radius: 99pt; font-size: 8pt; font-weight: bold;
        background: #e0e7ff; color: #3730a3;
    }
</style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td>
                <div class="header-title">{{ $clinicName }}</div>
                @if($clinicLegal)
                    <div class="header-sub">Razón social: {{ $clinicLegal }}</div>
                @endif
                @if($clinicAddress ?? null)
                    <div class="header-sub">{{ $clinicAddress }}</div>
                @endif
            </td>
            <td style="text-align:right; vertical-align:top;">
                <span class="badge">Firma Digital Simple</span>
                <div style="font-size:8pt; color:#555; margin-top:4pt;">Folio: {{ substr($documentHash, 0, 12) }}</div>
            </td>
        </tr>
    </table>

    <!-- Tipo de documento -->
    <div class="section">
        <div class="section-title">Documento firmado</div>
        <table class="data">
            <tr>
                <td class="lbl">Tipo:</td>
                <td>{{ $docLabel }}</td>
            </tr>
            <tr>
                <td class="lbl">Versión:</td>
                <td>{{ $version }}</td>
            </tr>
            <tr>
                <td class="lbl">Fecha y hora de firma:</td>
                <td>{{ $signedAt }}</td>
            </tr>
            <tr>
                <td class="lbl">Dirección IP:</td>
                <td>{{ $signedIp }}</td>
            </tr>
            <tr>
                <td class="lbl">Fuente:</td>
                <td>{{ $sourceLabel }}</td>
            </tr>
            <tr>
                <td class="lbl">Dispositivo:</td>
                <td style="font-size:8pt; color:#555;">{{ $userAgent }}</td>
            </tr>
        </table>
    </div>

    <!-- Firmante -->
    <div class="section">
        <div class="section-title">Datos del firmante</div>
        <table class="data">
            <tr>
                <td class="lbl">Paciente:</td>
                <td>{{ $patientName }}</td>
            </tr>
            <tr>
                <td class="lbl">Firmante:</td>
                <td>{{ $signerName }}</td>
            </tr>
            <tr>
                <td class="lbl">Rol:</td>
                <td>{{ $signerRoleLabel }}</td>
            </tr>
            @if($guardianName)
                <tr>
                    <td class="lbl">Tutor / Representante:</td>
                    <td>{{ $guardianName }}</td>
                </tr>
                <tr>
                    <td class="lbl">Parentesco:</td>
                    <td>{{ $guardianRelationship }}</td>
                </tr>
            @endif
        </table>
    </div>

    <!-- Firma dibujada -->
    @if($signatureImageBase64)
    <div class="section">
        <div class="section-title">Firma dibujada</div>
        <div class="sig-box">
            <img src="data:image/png;base64,{{ $signatureImageBase64 }}"
                 style="max-height:70pt; max-width:240pt; display:block;" />
        </div>
    </div>
    @endif

    <!-- Evidencia criptográfica -->
    <div class="section">
        <div class="section-title">Evidencia criptográfica</div>
        <table class="data">
            <tr>
                <td class="lbl" style="vertical-align:top;">Hash del documento:</td>
                <td><div class="hash-cell">{{ $documentHash }}</div></td>
            </tr>
            <tr>
                <td class="lbl" style="vertical-align:top;">Hash de firma:</td>
                <td><div class="hash-cell">{{ $signatureHash }}</div></td>
            </tr>
        </table>
    </div>

    <!-- Texto del documento aceptado -->
    <div class="section">
        <div class="section-title">Texto del documento aceptado</div>
        <div class="content-box">{{ $contentSnapshot ?: '(Texto no disponible)' }}</div>
    </div>

    <!-- Aviso legal -->
    <div class="disclaimer">
        <strong>Nota importante:</strong> Esta firma digital simple registra evidencia de aceptación dentro del
        sistema FisioVida. La clínica debe validar sus documentos y el método de firma con su asesor jurídico.
        Esta evidencia no constituye firma electrónica avanzada (FIEL/SAT) ni e.firma.
    </div>

    <div class="footer">
        Generado por FisioVida · {{ $generatedAt }} · Documento interno de uso clínico
    </div>

</div>
</body>
</html>
