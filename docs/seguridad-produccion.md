# Checklist de Seguridad para Producción — FisioVida

## Servidor y aplicación

- [ ] `APP_DEBUG=false` en `.env`
- [ ] `APP_ENV=production` en `.env`
- [ ] `APP_KEY` generada y guardada de forma segura
- [ ] HTTPS habilitado (certificado SSL/TLS válido)
- [ ] Redireccionamiento HTTP → HTTPS configurado en servidor web
- [ ] Headers de seguridad configurados (HSTS, X-Frame-Options, CSP básico)

## Sesiones y cookies

- [ ] `SESSION_DRIVER=database`
- [ ] `SESSION_ENCRYPT=true`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_SAME_SITE=lax`
- [ ] `SESSION_LIFETIME` ajustado según política de la clínica (recomendado: 120 min)

## Base de datos

- [ ] Usuario MySQL con privilegios mínimos (no root en producción)
- [ ] Contraseña fuerte para usuario MySQL
- [ ] Puerto 3306 no expuesto públicamente
- [ ] Backups automáticos diarios de MySQL cifrados
- [ ] Prueba de restauración ejecutada y documentada

## Archivos clínicos

- [ ] `FILESYSTEM_DISK=private`
- [ ] Archivos guardados en `storage/app/private` (fuera de public/)
- [ ] Acceso a archivos solo vía controlador autenticado
- [ ] Backup diario de `storage/app/private` cifrado
- [ ] Prueba de restauración de archivos ejecutada

## Correo

- [ ] `MAIL_MAILER=smtp`
- [ ] `MAIL_ENCRYPTION=tls`
- [ ] No se envían contraseñas en texto plano (alta de usuarios usa reset link)
- [ ] Cuenta SMTP dedicada para la aplicación (no cuenta personal)

## Accesos y contraseñas

- [ ] Registro público deshabilitado (`Features::registration()` comentado en `config/fortify.php`)
- [ ] Contraseñas de producción no en repositorio git
- [ ] `.env` en `.gitignore`
- [ ] Rotación de contraseñas de sistema documentada

## Roles y permisos

- [ ] Principio de mínimo privilegio: cada usuario solo tiene los permisos que necesita
- [ ] No hay usuarios con `is_super_admin=true` en producción excepto el administrador principal
- [ ] Revisión periódica de usuarios activos y sus roles

## Bitácora y auditoría

- [ ] Todas las acciones CRUD registradas en tabla `logs`
- [ ] Campos clínicos sensibles enmascarados en bitácora (`[REDACTADO]`)
- [ ] Acceso a bitácora restringido a usuarios con permiso `logs.view`
- [ ] Visualización y descarga de archivos auditada

## Cumplimiento LGPD / normativa clínica

- [ ] Aviso de privacidad redactado y visible en el sistema
- [ ] Consentimiento informado registrado por paciente (tabla `consents`)
- [ ] Retención de expedientes clínicos mínima 5 años desde último acto médico
- [ ] No borrado físico de expedientes clínicos sin flujo de retención aprobado

## Pendientes cumplimiento fase 2

- [ ] ARCO completo (Acceso, Rectificación, Cancelación, Oposición)
- [ ] Cifrado de campos sensibles en base de datos (notas clínicas)
- [ ] Retención y purga automática con revisión humana obligatoria
- [ ] RBAC fino terapeuta-paciente (terapeuta solo ve sus pacientes)
- [ ] Exportaciones de datos con control y auditoría
- [ ] Documento de Tratamiento de Datos Personales completo
- [ ] Evaluación de impacto de privacidad (PIA/DPIA)
