# Cumplimiento Normativo — FisioVida

## Marco legal aplicable (México)

| Ley / NOM | Relevancia |
|-----------|-----------|
| LFPDPPP (Ley Federal de Protección de Datos Personales en Posesión de los Particulares) | Datos personales y sensibles de pacientes |
| NOM-004-SSA3-2012 | Expediente clínico electrónico |
| NOM-024-SSA3-2010 | Sistemas de información de registro electrónico para la salud |
| LOPD (aplicación análoga) | Principios de minimización, finalidad y proporcionalidad |

---

## Tablas de cumplimiento en base de datos

### `patient_consents`

Registra consentimientos clínicos firmados o aceptados por el paciente.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `patient_persona_id` | FK personas | Paciente |
| `consent_type` | enum | `tratamiento`, `imagenes`, `datos_sensibles` |
| `accepted_at` | timestamp | Fecha y hora de aceptación |
| `accepted_by_user_id` | FK users | Usuario que registró |
| `notes` | text | Notas adicionales (ej. "firmado en papel") |
| `ip_address` | varchar | IP del registro |

### `privacy_notice_acceptances`

Tabla dedicada al aviso de privacidad conforme a la LFPDPPP (artículo 16).
Separada de `patient_consents` para trazabilidad independiente por obligación legal.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `patient_persona_id` | FK personas | Paciente |
| `version` | varchar | Versión del aviso (ej. "1.0", "2.0") |
| `accepted_at` | timestamp | Fecha y hora de aceptación |
| `accepted_by` | FK users | Usuario que registró |
| `ip` | varchar | IP del registro |
| `user_agent` | text | Navegador / agente del registro |

---

## Archivos clínicos — retención y borrado

### Política de borrado

Los archivos clínicos **nunca se eliminan físicamente** del sistema.

- `ArchivosController@destroy` realiza un **soft delete**: actualiza `deleted_at` y `deleted_by`.
- El archivo físico en disco permanece intacto.
- La tabla `files` incluye: `deleted_at`, `deleted_by`, `retention_until`, `purged_at`, `uuid`.

### Visibilidad

- `index`: excluye archivos con `deleted_at IS NOT NULL`.
- `show` / `download`: retorna 404 si el archivo tiene `deleted_at`.
- Archivos marcados como eliminados **no son accesibles** por la interfaz, pero persisten para auditoría y cumplimiento legal.

### Retención recomendada

Según NOM-004-SSA3-2012, el expediente clínico debe conservarse mínimo **5 años** desde la última consulta. Se recomienda configurar `retention_until` al insertar archivos.

---

## Auditoría de eventos clínicos

Todos los eventos se registran en la tabla `logs` con nivel `audit`.

| Evento (`action`) | Descripción |
|-------------------|-------------|
| `Visualización` (module: Pacientes) | Expediente consultado — vía `patientViewed()` |
| `Visualización` (module: Archivos) | Archivo visualizado en línea — vía `fileViewed()` |
| `Descarga` (module: Archivos) | Archivo descargado — vía `fileDownloaded()` |
| `Eliminación (soft)` | Archivo marcado como eliminado — vía `softDeleted()` |
| `consent_registered` | Consentimiento clínico registrado |
| `privacy_notice_accepted` | Aviso de privacidad aceptado |
| `Exportación Excel` | Reporte exportado en Excel |
| `Exportación PDF` | Reporte exportado en PDF |

Cada registro incluye: `user_id`, `ip_address`, `user_agent`, `human_message`, `old_values`, `new_values`.

Los campos sensibles (`password`, `email`, `telefono`, `notes`, etc.) son **redactados automáticamente** (función `maskSensitive()` recursiva) antes de guardarse en `logs`.

---

## Policies (Gates) — Control de acceso a archivos

`FilePolicy` registrada en `AppServiceProvider` con `Gate::policy(StoredFile::class, FilePolicy::class)`.

| Método Policy | Permiso requerido | Llamado desde |
|--------------|-------------------|---------------|
| `viewAny` | `files.view` ó superadmin | `index()` |
| `view` | `files.view` ó superadmin | `show()` |
| `download` | `files.view` ó superadmin | `download()` |
| `upload` | `files.upload` ó superadmin | `store()` |
| `update` | `files.upload` ó superadmin | `update()` |
| `delete` | `files.delete` ó superadmin | `destroy()` |

Cada método de `ArchivosController` llama `$this->authorize(...)` explícitamente, además de la protección por middleware `permission:`.

---

## Datos sensibles — minimización

- Campos `subjective`, `objective`, `assessment`, `plan` (notas SOAP) se consideran datos sensibles de salud.
- Nunca se envían en texto plano a logs de auditoría.
- Acceso restringido por permiso `sessions.view`.

---

## Correos electrónicos — seguridad

- **No se envían contraseñas en texto plano** por correo.
- Al crear un usuario, se genera un token de restablecimiento y se envía un enlace de 60 minutos.
- `UserCredentialsMail` solo contiene email de acceso + enlace de reset.
- El registro de nuevos pacientes/usuarios desde el formulario público está **deshabilitado** (Fortify registration desactivado).

---

## Recomendaciones adicionales para producción

1. Configurar `FILESYSTEM_DISK=private` — los archivos clínicos no deben ser accesibles públicamente.
2. Habilitar backups automáticos de la base de datos (mínimo diario).
3. Configurar rotación de logs (`LOG_CHANNEL=daily` o enviar a servicio externo).
4. Revisar `retention_until` en archivos para implementar purga automatizada (`purged_at`).
5. Capacitar al personal en el uso correcto del módulo de cumplimiento (tab "Cumplimiento" en expediente).
