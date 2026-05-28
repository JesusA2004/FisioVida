<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Cita confirmada</title></head>
<body style="margin:0;padding:0;background:#f4f7f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f8;padding:32px 16px;">
  <tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 14px 40px rgba(15,23,42,0.08);">
      <tr>
        <td style="padding:30px;background:linear-gradient(135deg,#059669,#0EA5A4);color:#ffffff;">
          <h1 style="margin:0;font-size:26px;font-weight:700;">✅ Cita confirmada</h1>
          <p style="margin:10px 0 0;font-size:15px;">Tu cita ha sido confirmada en FisioVida.</p>
        </td>
      </tr>
      <tr><td style="padding:30px;">
        <p style="margin:0 0 22px;font-size:15px;color:#4b5563;">Hola <strong>{{ $appointment['patient_name'] }}</strong>, te confirmamos tu cita:</p>
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;margin:0 0 24px;overflow:hidden;">
          <tr><td style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
            <p style="margin:0 0 4px;font-size:12px;color:#6b7280;text-transform:uppercase;">Terapeuta</p>
            <p style="margin:0;font-size:16px;font-weight:700;color:#111827;">{{ $appointment['therapist_name'] }}</p>
          </td></tr>
          <tr><td style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
            <p style="margin:0 0 4px;font-size:12px;color:#6b7280;text-transform:uppercase;">Fecha</p>
            <p style="margin:0;font-size:16px;font-weight:700;color:#111827;">{{ $date }}</p>
          </td></tr>
          <tr><td style="padding:16px 20px;">
            <p style="margin:0 0 4px;font-size:12px;color:#6b7280;text-transform:uppercase;">Horario</p>
            <p style="margin:0;font-size:16px;font-weight:700;color:#111827;">{{ $startTime }} – {{ $endTime }}</p>
          </td></tr>
        </table>
        <div style="text-align:center;margin:24px 0;">
          <a href="{{ $appointment['login_url'] ?? url('/login') }}" style="display:inline-block;background:#0EA5A4;color:#fff;text-decoration:none;padding:14px 26px;border-radius:16px;font-weight:700;font-size:15px;">Ver mi cita</a>
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
