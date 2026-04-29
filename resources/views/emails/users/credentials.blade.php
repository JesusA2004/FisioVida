<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tus accesos a FisioVida</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f8;padding:32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 14px 40px rgba(15,23,42,0.08);">
                    <tr>
                        <td style="padding:28px 30px;background:linear-gradient(135deg,#0EA5A4,#7DD03C);color:#ffffff;">
                            <h1 style="margin:0;font-size:26px;line-height:1.2;font-weight:700;">
                                Bienvenido a FisioVida
                            </h1>
                            <p style="margin:10px 0 0;font-size:15px;line-height:1.6;">
                                Se creó tu cuenta de acceso al sistema.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:30px;">
                            <p style="margin:0 0 18px;font-size:16px;line-height:1.7;">
                                Hola <strong>{{ $user->name }}</strong>,
                            </p>

                            <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#4b5563;">
                                Estas son tus credenciales para ingresar al sistema. Te recomendamos cambiar tu contraseña después de iniciar sesión por primera vez.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:0;margin:0 0 24px;">
                                <tr>
                                    <td style="padding:18px 20px;border-bottom:1px solid #e5e7eb;">
                                        <p style="margin:0 0 6px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">
                                            Correo
                                        </p>
                                        <p style="margin:0;font-size:16px;font-weight:700;color:#111827;">
                                            {{ $user->email }}
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:18px 20px;">
                                        <p style="margin:0 0 6px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">
                                            Contraseña temporal
                                        </p>
                                        <p style="margin:0;font-size:18px;font-weight:700;color:#111827;letter-spacing:.02em;">
                                            {{ $plainPassword }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <div style="text-align:center;margin:28px 0;">
                                <a href="{{ $loginUrl }}" style="display:inline-block;background:#0EA5A4;color:#ffffff;text-decoration:none;padding:14px 26px;border-radius:16px;font-weight:700;font-size:15px;">
                                    Iniciar sesión
                                </a>
                            </div>

                            <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#6b7280;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:
                            </p>

                            <p style="margin:0;font-size:13px;line-height:1.6;word-break:break-all;color:#0EA5A4;">
                                {{ $loginUrl }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 30px;background:#f8fafc;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#6b7280;text-align:center;">
                                Este correo fue enviado automáticamente por FisioVida.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
