<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido a tu portal — {{ $clinicName }}</title>
</head>
<body style="margin:0;padding:0;background:#f0f9ff;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f9ff;padding:36px 16px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #bae6fd;box-shadow:0 12px 36px rgba(15,23,42,0.10);">

                <!-- Header -->
                <tr>
                    <td style="padding:32px 36px 28px;background:linear-gradient(135deg,#059AB2 0%,#0ea5e9 100%);">
                        <p style="margin:0 0 4px;font-size:13px;color:rgba(255,255,255,0.75);letter-spacing:0.05em;text-transform:uppercase;font-weight:600;">{{ $clinicName }}</p>
                        <h1 style="margin:0;font-size:28px;line-height:1.2;font-weight:800;color:#ffffff;">
                            ¡Hola, {{ explode(' ', $user->name)[0] }}!
                        </h1>
                        <p style="margin:10px 0 0;font-size:15px;line-height:1.6;color:rgba(255,255,255,0.90);">
                            Tu cuenta de paciente está lista. Ya puedes acceder a tu portal personal.
                        </p>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:32px 36px;">

                        <p style="margin:0 0 20px;font-size:15px;line-height:1.8;color:#334155;">
                            Creamos tu cuenta para que puedas ver tus citas, ejercicios, archivos clínicos y mucho más — todo desde un solo lugar.
                        </p>

                        <!-- Pending docs alert -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:16px;margin-bottom:24px;">
                            <tr>
                                <td style="padding:18px 22px;">
                                    <p style="margin:0 0 6px;font-size:14px;font-weight:700;color:#c2410c;">
                                        📋 Documentos pendientes por firmar
                                    </p>
                                    <p style="margin:0;font-size:14px;line-height:1.7;color:#7c2d12;">
                                        Antes de tu primera cita necesitamos que leas y firmes digitalmente tu
                                        <strong>Aviso de Privacidad</strong> y <strong>Consentimiento de Tratamiento</strong>.
                                        Solo toma unos minutos y puedes hacerlo directamente en tu portal.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <!-- What you can do -->
                        <p style="margin:0 0 12px;font-size:14px;font-weight:700;color:#0f172a;">En tu portal podrás:</p>
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                            @foreach([
                                ['📅', 'Ver y gestionar tus próximas citas'],
                                ['💪', 'Revisar los ejercicios asignados por tu terapeuta'],
                                ['📂', 'Acceder a tus archivos y estudios clínicos'],
                                ['📝', 'Firmar tus consentimientos y aviso de privacidad'],
                                ['📊', 'Seguir el progreso de tu tratamiento'],
                            ] as $item)
                            <tr>
                                <td style="padding:5px 0;font-size:14px;line-height:1.6;color:#334155;">
                                    <span style="margin-right:8px;">{{ $item[0] }}</span>{{ $item[1] }}
                                </td>
                            </tr>
                            @endforeach
                        </table>

                        <!-- CTA buttons -->
                        <div style="text-align:center;margin:30px 0 20px;">
                            <a href="{{ $portalUrl }}"
                               style="display:inline-block;background:#059AB2;color:#ffffff;text-decoration:none;padding:15px 32px;border-radius:16px;font-weight:700;font-size:16px;margin-bottom:10px;">
                                Ir a mi portal →
                            </a>
                        </div>

                        <p style="margin:0 0 8px;font-size:13px;color:#6b7280;text-align:center;">
                            ¿Primera vez? Establece tu contraseña con este enlace:
                        </p>
                        <div style="text-align:center;margin-bottom:24px;">
                            <a href="{{ $resetUrl }}"
                               style="display:inline-block;background:#f1f5f9;color:#0f172a;text-decoration:none;padding:12px 24px;border-radius:12px;font-weight:600;font-size:14px;border:1px solid #e2e8f0;">
                                Establecer contraseña
                            </a>
                        </div>

                        <!-- Login info -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;margin-bottom:8px;">
                            <tr>
                                <td style="padding:16px 20px;">
                                    <p style="margin:0 0 4px;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;">Tu correo de acceso</p>
                                    <p style="margin:0;font-size:16px;font-weight:700;color:#0f172a;">{{ $user->email }}</p>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:20px 36px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:12px;line-height:1.7;color:#94a3b8;text-align:center;">
                            Este correo fue enviado automáticamente por <strong style="color:#64748b;">{{ $clinicName }}</strong>.<br>
                            Si no esperabas este correo o crees que es un error, escríbenos a tu clínica.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
