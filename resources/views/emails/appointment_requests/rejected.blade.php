<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Solicitud no disponible</title></head>
<body style="margin:0;padding:0;background:#f4f7f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f8;padding:32px 16px;">
  <tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 14px 40px rgba(15,23,42,0.08);">
      <tr>
        <td style="padding:30px;background:linear-gradient(135deg,#d97706,#dc2626);color:#ffffff;">
          <h1 style="margin:0;font-size:26px;font-weight:700;">📋 Fecha no disponible</h1>
          <p style="margin:10px 0 0;font-size:15px;">Tu solicitud de cita no pudo ser agendada en esa fecha.</p>
        </td>
      </tr>
      <tr><td style="padding:30px;">
        <p style="margin:0 0 16px;font-size:15px;color:#4b5563;">Hola <strong>{{ $patient_name }}</strong>,</p>
        <p style="margin:0 0 22px;font-size:15px;color:#4b5563;">Lamentablemente no pudimos agendar tu cita en la fecha solicitada{{ $date_label ? ' ('.$date_label.')' : '' }}.</p>
        @if($rejection_reason)
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:18px;margin:0 0 24px;overflow:hidden;">
          <tr><td style="padding:16px 20px;">
            <p style="margin:0 0 4px;font-size:12px;color:#92400e;text-transform:uppercase;">Motivo</p>
            <p style="margin:0;font-size:14px;color:#78350f;">{{ $rejection_reason }}</p>
          </td></tr>
        </table>
        @endif
        <p style="font-size:14px;color:#4b5563;">Puedes enviar una nueva solicitud con otra fecha disponible desde tu portal.</p>
        <div style="text-align:center;margin:24px 0;">
          <a href="{{ $login_url }}" style="display:inline-block;background:#0EA5A4;color:#fff;text-decoration:none;padding:14px 26px;border-radius:16px;font-weight:700;font-size:15px;">Solicitar otra cita</a>
        </div>
      </td></tr>
      <tr><td style="padding:18px 30px;background:#f8fafc;border-top:1px solid #e5e7eb;">
        <p style="margin:0;font-size:12px;color:#6b7280;text-align:center;">Este correo fue enviado automáticamente por FisioVida.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
