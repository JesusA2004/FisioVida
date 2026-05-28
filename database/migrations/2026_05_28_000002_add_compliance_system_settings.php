<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $defaults = [
            // ── Identidad legal ──────────────────────────────────────────────
            ['key' => 'clinic_sector',              'value' => 'physiotherapy', 'group' => 'compliance', 'label' => 'Sector de salud', 'is_public' => false, 'type' => 'string'],
            ['key' => 'legal_business_name',        'value' => '',              'group' => 'compliance', 'label' => 'Razón social / nombre legal', 'is_public' => false, 'type' => 'string'],
            ['key' => 'legal_representative',       'value' => '',              'group' => 'compliance', 'label' => 'Representante legal', 'is_public' => false, 'type' => 'string'],
            ['key' => 'privacy_responsible_name',   'value' => '',              'group' => 'compliance', 'label' => 'Responsable de datos personales', 'is_public' => false, 'type' => 'string'],
            ['key' => 'privacy_contact_email',      'value' => '',              'group' => 'compliance', 'label' => 'Correo para derechos ARCO', 'is_public' => false, 'type' => 'string'],
            ['key' => 'privacy_contact_phone',      'value' => '',              'group' => 'compliance', 'label' => 'Teléfono para privacidad', 'is_public' => false, 'type' => 'string'],
            ['key' => 'privacy_address',            'value' => '',              'group' => 'compliance', 'label' => 'Domicilio para derechos ARCO', 'is_public' => false, 'type' => 'string'],

            // ── Versión y vigencia ───────────────────────────────────────────
            ['key' => 'privacy_notice_version',        'value' => '1.0',        'group' => 'compliance', 'label' => 'Versión del aviso de privacidad', 'is_public' => false, 'type' => 'string'],
            ['key' => 'privacy_notice_effective_date', 'value' => date('Y-m-d'), 'group' => 'compliance', 'label' => 'Fecha de vigencia del aviso', 'is_public' => false, 'type' => 'string'],

            // ── Textos de documentos ─────────────────────────────────────────
            ['key' => 'privacy_notice_text',        'value' => self::defaultPrivacyNoticeText(), 'group' => 'compliance', 'label' => 'Texto del aviso de privacidad',                    'is_public' => false, 'type' => 'text'],
            ['key' => 'sensitive_data_consent_text','value' => self::defaultSensitiveDataText(),  'group' => 'compliance', 'label' => 'Texto de consentimiento de datos sensibles',       'is_public' => false, 'type' => 'text'],
            ['key' => 'treatment_consent_text',     'value' => self::defaultTreatmentText(),      'group' => 'compliance', 'label' => 'Texto de consentimiento de tratamiento',           'is_public' => false, 'type' => 'text'],
            ['key' => 'image_consent_text',         'value' => self::defaultImageText(),          'group' => 'compliance', 'label' => 'Texto de consentimiento de imágenes/evidencia',   'is_public' => false, 'type' => 'text'],
            ['key' => 'minor_consent_text',         'value' => self::defaultMinorText(),          'group' => 'compliance', 'label' => 'Texto de consentimiento para menor de edad',       'is_public' => false, 'type' => 'text'],
            ['key' => 'data_retention_policy_text', 'value' => self::defaultRetentionText(),      'group' => 'compliance', 'label' => 'Política de conservación del expediente',          'is_public' => false, 'type' => 'text'],

            // ── Controles de flujo ───────────────────────────────────────────
            ['key' => 'allow_patient_portal_acceptance',         'value' => '1', 'group' => 'compliance', 'label' => 'Permitir aceptación desde portal paciente', 'is_public' => false, 'type' => 'boolean'],
            ['key' => 'require_privacy_notice_before_session',   'value' => '0', 'group' => 'compliance', 'label' => 'Exigir aviso de privacidad antes de sesión', 'is_public' => false, 'type' => 'boolean'],
            ['key' => 'require_treatment_consent_before_session','value' => '0', 'group' => 'compliance', 'label' => 'Exigir consentimiento de tratamiento antes de sesión', 'is_public' => false, 'type' => 'boolean'],
            ['key' => 'require_image_consent_for_uploads',       'value' => '0', 'group' => 'compliance', 'label' => 'Exigir consentimiento de imágenes para evidencia fotográfica', 'is_public' => false, 'type' => 'boolean'],
        ];

        foreach ($defaults as $row) {
            DB::table('system_settings')->insertOrIgnore([
                'key'         => $row['key'],
                'value'       => $row['value'],
                'type'        => $row['type'],
                'group'       => $row['group'],
                'label'       => $row['label'],
                'description' => null,
                'is_public'   => $row['is_public'] ? 1 : 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        DB::table('system_settings')->whereIn('key', [
            'clinic_sector', 'legal_business_name', 'legal_representative',
            'privacy_responsible_name', 'privacy_contact_email', 'privacy_contact_phone',
            'privacy_address', 'privacy_notice_version', 'privacy_notice_effective_date',
            'privacy_notice_text', 'sensitive_data_consent_text', 'treatment_consent_text',
            'image_consent_text', 'minor_consent_text', 'data_retention_policy_text',
            'allow_patient_portal_acceptance', 'require_privacy_notice_before_session',
            'require_treatment_consent_before_session', 'require_image_consent_for_uploads',
        ])->delete();
    }

    private static function defaultPrivacyNoticeText(): string
    {
        return <<<'TXT'
AVISO DE PRIVACIDAD

[Nombre de la clínica], con domicilio en [dirección], es responsable del tratamiento de sus datos personales conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP).

FINALIDADES
Sus datos se utilizan para: identificación como paciente; gestión del expediente clínico; prestación de servicios de salud; programación de citas; comunicación relacionada con su atención; y administración del servicio.

DATOS QUE SE RECABAN
Datos de identificación y contacto. Datos sensibles de salud: diagnóstico, historial clínico, evolución del tratamiento, fotografías de evidencia clínica.

DERECHOS ARCO
Puede ejercer sus derechos de Acceso, Rectificación, Cancelación u Oposición escribiendo a [correo ARCO] o llamando a [teléfono].

TRANSFERENCIAS
No se comparten datos con terceros salvo obligación legal.

NOTA: Este texto es una plantilla editable. La clínica debe validar su contenido con asesoría jurídica antes de utilizarlo.
TXT;
    }

    private static function defaultSensitiveDataText(): string
    {
        return <<<'TXT'
CONSENTIMIENTO PARA TRATAMIENTO DE DATOS SENSIBLES DE SALUD

Conforme al artículo 9 de la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP), se requiere su consentimiento expreso para el tratamiento de datos sensibles de salud.

Los datos de salud que se recaban incluyen: diagnóstico médico, historial clínico, notas de evolución, fotografías o imágenes de evidencia clínica, escala de dolor y plan de tratamiento.

Estos datos son tratados exclusivamente para brindarle atención de salud, dar seguimiento a su tratamiento, mantener su expediente clínico y facilitar la comunicación entre usted y el equipo de salud.

Sus datos no se compartirán con terceros sin su consentimiento, salvo obligación legal.

NOTA: Este texto es una plantilla editable. La clínica debe validar su contenido con asesoría jurídica.
TXT;
    }

    private static function defaultTreatmentText(): string
    {
        return <<<'TXT'
CONSENTIMIENTO INFORMADO DE TRATAMIENTO

Declaro que he recibido información sobre mi diagnóstico, el tratamiento propuesto, sus beneficios esperados y los riesgos asociados. He tenido la oportunidad de realizar preguntas y éstas han sido respondidas satisfactoriamente.

Autorizo a [nombre de clínica] y a su equipo de profesionales de la salud a llevar a cabo el tratamiento indicado, entendiendo que puedo revocar este consentimiento en cualquier momento salvo que el tratamiento ya hubiera iniciado y su interrupción pudiera afectar mi salud.

NOTA: Este texto es una plantilla editable. La clínica debe validar su contenido con asesoría jurídica y ajustarlo a su especialidad de atención.
TXT;
    }

    private static function defaultImageText(): string
    {
        return <<<'TXT'
AUTORIZACIÓN PARA USO DE IMÁGENES Y EVIDENCIA CLÍNICA

Autorizo a [nombre de clínica] a tomar y registrar fotografías, videos u otros registros visuales de mi persona con fines exclusivamente clínicos: documentación del progreso del tratamiento, seguimiento terapéutico y comunicación médica interna.

Estas imágenes formarán parte de mi expediente clínico y serán tratadas de forma confidencial. No se utilizarán con fines comerciales ni publicitarios sin mi consentimiento específico y por escrito.

El acceso a estas imágenes se limita al personal autorizado de la clínica involucrado en mi atención.

NOTA: Este texto es una plantilla editable. La clínica debe validar su contenido con asesoría jurídica.
TXT;
    }

    private static function defaultMinorText(): string
    {
        return <<<'TXT'
CONSENTIMIENTO DE TUTOR O RESPONSABLE LEGAL PARA MENOR DE EDAD

Yo, [nombre del tutor o responsable legal], en mi carácter de [parentesco / tutela] del menor [nombre del menor], declaro que:

1. Cuento con la representación legal o patria potestad del menor.
2. He sido informado(a) sobre el diagnóstico, el tratamiento propuesto y sus alcances.
3. Autorizo a [nombre de clínica] a prestar los servicios de salud necesarios al menor.
4. Autorizo el tratamiento de los datos personales y datos sensibles de salud del menor conforme al Aviso de Privacidad de la clínica.

NOTA: Este texto es una plantilla editable. La clínica debe validar su contenido con asesoría jurídica.
TXT;
    }

    private static function defaultRetentionText(): string
    {
        return <<<'TXT'
POLÍTICA DE CONSERVACIÓN DEL EXPEDIENTE CLÍNICO

El expediente clínico se conserva por un período mínimo de 5 años a partir de la última consulta, conforme a buenas prácticas clínicas y normativas aplicables.

Transcurrido el período de conservación, los datos se eliminarán de forma segura, salvo que exista obligación legal de conservarlos por un plazo mayor.

El paciente puede solicitar una copia de su expediente clínico ejerciendo sus derechos ARCO conforme al Aviso de Privacidad.

NOTA: Este texto es una plantilla editable. La clínica debe validar su contenido con asesoría jurídica.
TXT;
    }
};
