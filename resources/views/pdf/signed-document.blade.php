<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 9.5pt;
        color: #1a202c;
        line-height: 1.5;
        background: #ffffff;
    }
    .page { padding: 16mm 20mm 14mm 20mm; }

    /* ── Header ── */
    .header { border-bottom: 3px solid #059AB2; padding-bottom: 10pt; margin-bottom: 14pt; }
    .header-inner { width: 100%; border-collapse: collapse; }
    .clinic-name { font-size: 16pt; font-weight: bold; color: #059AB2; letter-spacing: -0.3pt; }
    .clinic-sub { font-size: 8.5pt; color: #64748b; margin-top: 2pt; }
    .doc-badge {
        background: #e0f7fa; border: 1pt solid #059AB2;
        padding: 4pt 10pt; border-radius: 4pt;
        font-size: 8pt; font-weight: bold; color: #0e7490;
        display: inline-block;
    }
    .folio { font-size: 7.5pt; color: #94a3b8; margin-top: 4pt; text-align: right; }

    /* ── Section ── */
    .section { margin-bottom: 13pt; page-break-inside: avoid; }
    .section-title {
        font-size: 8.5pt; font-weight: bold; text-transform: uppercase;
        letter-spacing: 0.5pt; color: #059AB2;
        border-bottom: 1pt solid #bae6fd; padding-bottom: 3pt; margin-bottom: 7pt;
    }

    /* ── Data table ── */
    .data { width: 100%; border-collapse: collapse; }
    .data tr { border-bottom: 0.5pt solid #f1f5f9; }
    .data td { padding: 3pt 4pt; vertical-align: top; font-size: 9pt; }
    .data td.lbl { font-weight: bold; color: #475569; width: 36%; font-size: 8.5pt; }
    .data td.val { color: #1e293b; }

    /* ── Document text — allow page break since content can be long ── */
    .content-box {
        background: #f8fafc; border: 1pt solid #e2e8f0;
        border-left: 3pt solid #059AB2;
        padding: 10pt 12pt; font-size: 8.5pt;
        white-space: pre-wrap; line-height: 1.55; color: #334155;
        border-radius: 0 4pt 4pt 0;
        page-break-inside: auto;
    }
    .section-content { page-break-inside: auto; }

    /* ── Signature image ── */
    .sig-wrapper {
        background: #ffffff; border: 1pt solid #cbd5e1;
        border-radius: 6pt; padding: 8pt 12pt;
        display: inline-block; min-width: 200pt; min-height: 60pt;
    }
    .sig-label { font-size: 7.5pt; color: #94a3b8; margin-top: 4pt; text-align: center; }

    /* ── Traceability ── */
    .trace-box {
        background: #f8fafc; border: 1pt dashed #cbd5e1;
        border-radius: 4pt; padding: 8pt 10pt;
    }
    .hash-cell {
        font-family: 'Courier New', monospace; font-size: 7pt;
        color: #475569; word-break: break-all; line-height: 1.4;
    }

    /* ── Legal note ── */
    .legal-note {
        background: #fffbeb; border: 1pt solid #fcd34d;
        border-radius: 4pt; padding: 7pt 10pt;
        font-size: 8pt; color: #78350f; margin-top: 10pt;
        page-break-inside: avoid;
    }
    .legal-note strong { color: #92400e; }

    /* ── Footer ── */
    .footer {
        border-top: 1pt solid #e2e8f0; margin-top: 14pt;
        padding-top: 7pt; text-align: center;
        font-size: 7.5pt; color: #94a3b8;
        page-break-inside: avoid;
    }
    .footer strong { color: #64748b; }

    /* ── Status badge ── */
    .status-accepted {
        background: #dcfce7; color: #15803d;
        padding: 2pt 8pt; border-radius: 99pt;
        font-size: 8pt; font-weight: bold; display: inline-block;
    }

    .two-col { width: 100%; border-collapse: collapse; }
    .two-col td { vertical-align: top; width: 50%; padding-right: 8pt; }
    .two-col td:last-child { padding-right: 0; }
</style>
</head>
<body>
<div class="page">

    <!-- ══ Header ══ -->
    <div class="header">
        <table class="header-inner">
            <tr>
                <td>
                    <div class="clinic-name">{{ $clinicName }}</div>
                    @if($clinicLegal && $clinicLegal !== $clinicName)
                        <div class="clinic-sub">{{ $clinicLegal }}</div>
                    @endif
                    @if($clinicAddress ?? null)
                        <div class="clinic-sub">{{ $clinicAddress }}</div>
                    @endif
                </td>
                <td style="text-align:right; vertical-align:top; width:40%;">
                    <div class="doc-badge">Firma Digital Simple</div>
                    <div class="folio">Folio: {{ strtoupper(substr($documentHash, 0, 16)) }}</div>
                    <div class="folio">Generado: {{ $generatedAt }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ══ Documento y estado ══ -->
    <div class="section">
        <div class="section-title">Documento</div>
        <table class="data">
            <tr>
                <td class="lbl">Tipo de documento:</td>
                <td class="val"><strong>{{ $docLabel }}</strong></td>
            </tr>
            <tr>
                <td class="lbl">Estado:</td>
                <td class="val"><span class="status-accepted">✓ Aceptado</span></td>
            </tr>
            <tr>
                <td class="lbl">Fecha y hora de firma:</td>
                <td class="val"><strong>{{ $signedAt }}</strong></td>
            </tr>
            <tr>
                <td class="lbl">Firmado a través de:</td>
                <td class="val">{{ $sourceLabel }}</td>
            </tr>
        </table>
    </div>

    <!-- ══ Datos del firmante ══ -->
    <div class="section">
        <div class="section-title">Datos del firmante</div>
        <table class="two-col">
            <tr>
                <td>
                    <table class="data">
                        <tr>
                            <td class="lbl">Paciente:</td>
                            <td class="val">{{ $patientName }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Firmante:</td>
                            <td class="val"><strong>{{ $signerName }}</strong></td>
                        </tr>
                        <tr>
                            <td class="lbl">Rol:</td>
                            <td class="val">{{ $signerRoleLabel }}</td>
                        </tr>
                        @if($guardianName)
                        <tr>
                            <td class="lbl">Tutor / Responsable:</td>
                            <td class="val">{{ $guardianName }}</td>
                        </tr>
                        @if($guardianRelationship)
                        <tr>
                            <td class="lbl">Parentesco:</td>
                            <td class="val">{{ $guardianRelationship }}</td>
                        </tr>
                        @endif
                        @endif
                    </table>
                </td>
                @if($signatureImageBase64)
                <td style="text-align:center; vertical-align:middle;">
                    <div class="sig-wrapper">
                        <img src="data:image/png;base64,{{ $signatureImageBase64 }}"
                             style="max-height:55pt; max-width:180pt; display:block; margin:auto;" />
                    </div>
                    <div class="sig-label">Firma autógrafa digital</div>
                </td>
                @endif
            </tr>
        </table>
    </div>

    <!-- ══ Texto del documento aceptado ══ -->
    <div class="section" style="page-break-inside: auto;">
        <div class="section-title">Texto del documento aceptado</div>
        <div class="content-box">{{ $contentSnapshot ?: '(Texto del documento registrado en el sistema)' }}</div>
    </div>

    <!-- ══ Datos de trazabilidad ══ -->
    <div class="section">
        <div class="section-title">Datos de trazabilidad</div>
        <div class="trace-box">
            <table class="data">
                <tr>
                    <td class="lbl" style="width:30%; font-size:8pt;">IP del firmante:</td>
                    <td class="val" style="font-size:8pt;">{{ $signedIp }}</td>
                </tr>
                @if($userAgent)
                <tr>
                    <td class="lbl" style="width:30%; font-size:8pt; vertical-align:top;">Dispositivo:</td>
                    <td class="val" style="font-size:7.5pt; color:#64748b;">{{ $userAgent }}</td>
                </tr>
                @endif
                <tr>
                    <td class="lbl" style="width:30%; font-size:8pt; vertical-align:top;">Hash del documento:</td>
                    <td><div class="hash-cell">{{ $documentHash }}</div></td>
                </tr>
                @if($signatureHash && $signatureHash !== '—')
                <tr>
                    <td class="lbl" style="width:30%; font-size:8pt; vertical-align:top;">Hash de firma:</td>
                    <td><div class="hash-cell">{{ $signatureHash }}</div></td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- ══ Nota legal ══ -->
    <div class="legal-note">
        <strong>Nota:</strong> Este documento registra la evidencia de aceptación dentro del sistema FisioVida.
        Constituye <strong>firma digital simple</strong> conforme al artículo 89 del Código de Comercio y sirve como
        respaldo interno del consentimiento informado. No equivale a firma electrónica avanzada (FIEL/SAT) ni e.firma.
        La clínica debe validar la validez jurídica de sus documentos con asesoría legal.
    </div>

    <!-- ══ Footer ══ -->
    <div class="footer">
        <strong>{{ $clinicName }}</strong> &nbsp;·&nbsp; Documento de uso clínico interno &nbsp;·&nbsp; {{ $generatedAt }}
    </div>

</div>
</body>
</html>
