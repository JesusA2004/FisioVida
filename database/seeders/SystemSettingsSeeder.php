<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── Identidad de clínica ───────────────────────────────────────────
            ['key' => 'clinic_name',           'value' => 'FisioVida',                                  'group' => 'clinic',      'label' => 'Nombre de clínica',          'is_public' => true],
            ['key' => 'clinic_logo',           'value' => '/ogLogo.png',                                'group' => 'clinic',      'label' => 'Logo',                       'is_public' => true],
            ['key' => 'clinic_phone',          'value' => '+52 55 1234 5678',                           'group' => 'clinic',      'label' => 'Teléfono',                   'is_public' => false],
            ['key' => 'clinic_email',          'value' => 'contacto@fisioclinica.mx',                   'group' => 'clinic',      'label' => 'Email de contacto',          'is_public' => false],
            ['key' => 'clinic_address',        'value' => 'Av. Insurgentes Sur 1234, Col. Del Valle, Ciudad de México, C.P. 03100', 'group' => 'clinic', 'label' => 'Dirección', 'is_public' => false],

            // ── Colores de tema ────────────────────────────────────────────────
            ['key' => 'primary_color',            'value' => '#059AB2', 'group' => 'appearance', 'label' => 'Color primario',         'is_public' => true],
            ['key' => 'primary_hover_color',      'value' => '#047A8E', 'group' => 'appearance', 'label' => 'Hover de botones',       'is_public' => true],
            ['key' => 'primary_foreground_color', 'value' => '#FFFFFF', 'group' => 'appearance', 'label' => 'Texto en botones',       'is_public' => true],
            ['key' => 'app_background_color',     'value' => '#EDF2F7', 'group' => 'appearance', 'label' => 'Fondo general',          'is_public' => true],
            ['key' => 'card_background_color',    'value' => '#FFFFFF', 'group' => 'appearance', 'label' => 'Fondo de tarjetas',      'is_public' => true],
            ['key' => 'sidebar_background_color', 'value' => '#FFFFFF', 'group' => 'appearance', 'label' => 'Fondo del menú lateral', 'is_public' => true],

            // ── Generales ──────────────────────────────────────────────────────
            ['key' => 'default_currency',             'value' => 'MXN', 'group' => 'general', 'label' => 'Moneda',          'is_public' => false],
            ['key' => 'appointment_default_duration', 'value' => '60',  'group' => 'general', 'label' => 'Duración de cita', 'is_public' => false],
            ['key' => 'demo_mode',                   'value' => '1',   'group' => 'general', 'label' => 'Modo demo',         'is_public' => true],
            ['key' => 'dark_mode_enabled',           'value' => '0',   'group' => 'general', 'label' => 'Modo oscuro',       'is_public' => false],

            // ── Cumplimiento legal ─────────────────────────────────────────────
            ['key' => 'clinic_sector',                          'value' => 'physiotherapy',         'group' => 'compliance', 'label' => 'Sector de salud',                  'is_public' => false],
            ['key' => 'legal_business_name',                   'value' => 'FisioVida Servicios Clínicos S.A. de C.V.', 'group' => 'compliance', 'label' => 'Razón social', 'is_public' => false],
            ['key' => 'legal_representative',                  'value' => 'Mariana Torres Ríos',   'group' => 'compliance', 'label' => 'Representante legal',              'is_public' => false],
            ['key' => 'privacy_contact_email',                 'value' => 'privacidad@fisioclinica.mx', 'group' => 'compliance', 'label' => 'Email responsable de privacidad', 'is_public' => false],
            ['key' => 'privacy_contact_phone',                 'value' => '+52 55 1234 5678',       'group' => 'compliance', 'label' => 'Teléfono de privacidad',          'is_public' => false],
            ['key' => 'privacy_address',                       'value' => 'Av. Insurgentes Sur 1234, Col. Del Valle, Ciudad de México, C.P. 03100', 'group' => 'compliance', 'label' => 'Domicilio legal', 'is_public' => false],
            ['key' => 'privacy_notice_version',                'value' => '2.0',                    'group' => 'compliance', 'label' => 'Versión del aviso',               'is_public' => false],
            ['key' => 'privacy_notice_effective_date',         'value' => '2025-01-01',             'group' => 'compliance', 'label' => 'Fecha de vigencia',               'is_public' => false],
            ['key' => 'allow_patient_portal_acceptance',       'value' => '1',                      'group' => 'compliance', 'label' => 'Aceptación desde portal',         'is_public' => false],
            ['key' => 'require_privacy_notice_before_session', 'value' => '0',                      'group' => 'compliance', 'label' => 'Requerir aviso antes de sesión',  'is_public' => false],
            ['key' => 'require_treatment_consent_before_session', 'value' => '0',                   'group' => 'compliance', 'label' => 'Requerir consentimiento antes de sesión', 'is_public' => false],
            ['key' => 'require_image_consent_for_uploads',     'value' => '0',                      'group' => 'compliance', 'label' => 'Requerir consentimiento de imagen', 'is_public' => false],

            // ── Textos legales (LFPDPPP / NOM-004) ────────────────────────────
            ['key' => 'privacy_notice_text', 'group' => 'compliance', 'label' => 'Aviso de privacidad', 'is_public' => false, 'value' =>
'AVISO DE PRIVACIDAD
FisioVida Servicios Clínicos S.A. de C.V.

FisioVida, con domicilio en Av. Insurgentes Sur 1234, Col. Del Valle, Ciudad de México, C.P. 03100, es responsable del uso y protección de sus datos personales, en apego a lo dispuesto en la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP).

DATOS QUE RECABAMOS
Nombre completo, fecha de nacimiento, domicilio, teléfono, correo electrónico, datos de salud, historial clínico, diagnósticos y notas de tratamiento.

FINALIDADES DEL TRATAMIENTO
• Primarias: prestación de servicios de fisioterapia y rehabilitación, seguimiento de tratamientos, elaboración y gestión del expediente clínico.
• Secundarias: comunicación sobre citas, recordatorios de sesiones y mejora del servicio. Puede oponerse a las finalidades secundarias contactando al responsable.

TRANSFERENCIAS
Sus datos no serán transferidos a terceros, salvo obligación legal o consentimiento expreso.

DERECHOS ARCO
Puede ejercer sus derechos de Acceso, Rectificación, Cancelación u Oposición enviando una solicitud a:
• Correo: privacidad@fisioclinica.mx
• Teléfono: +52 55 1234 5678
• Domicilio: Av. Insurgentes Sur 1234, Col. Del Valle, Ciudad de México

CAMBIOS AL AVISO
Cualquier modificación será notificada a través del portal del paciente o por correo electrónico.

Vigencia: 01 de enero de 2025  |  Versión 2.0'],

            ['key' => 'sensitive_data_consent_text', 'group' => 'compliance', 'label' => 'Consentimiento datos sensibles', 'is_public' => false, 'value' =>
'CONSENTIMIENTO PARA EL TRATAMIENTO DE DATOS PERSONALES SENSIBLES
FisioVida — Fisioterapia y Rehabilitación

En cumplimiento con el artículo 9 de la LFPDPPP, solicito su consentimiento expreso para el tratamiento de sus datos personales sensibles.

DATOS SENSIBLES A TRATAR
• Estado de salud actual y antecedentes médicos
• Diagnósticos clínicos y resultados de evaluaciones físicas
• Historial de lesiones, cirugías y padecimientos previos
• Escalas de dolor y evolución del tratamiento
• Fotografías o videos clínicos con fines de evaluación y seguimiento

FINALIDAD
Estos datos se utilizan exclusivamente para la correcta prestación del servicio de fisioterapia y rehabilitación, documentación del expediente clínico y cumplimiento de obligaciones sanitarias establecidas en la NOM-004-SSA3-2012.

SEGURIDAD
FisioVida implementa medidas de seguridad administrativas, técnicas y físicas para proteger sus datos contra daño, pérdida, alteración o acceso no autorizado.

VIGENCIA
Sus datos se conservarán durante el tiempo que dure la relación de atención médica y conforme lo establezca la normativa sanitaria vigente.

Al firmar este documento, manifiesta su consentimiento libre, específico e informado para el tratamiento de sus datos personales sensibles con las finalidades descritas.

FisioVida Servicios Clínicos S.A. de C.V. | privacidad@fisioclinica.mx'],

            ['key' => 'treatment_consent_text', 'group' => 'compliance', 'label' => 'Consentimiento de tratamiento', 'is_public' => false, 'value' =>
'CONSENTIMIENTO INFORMADO PARA TRATAMIENTO DE FISIOTERAPIA
FisioVida — Fisioterapia y Rehabilitación

De conformidad con la NOM-004-SSA3-2012 sobre el expediente clínico, y en ejercicio de mi autonomía como paciente, otorgo el presente consentimiento informado.

DESCRIPCIÓN DEL TRATAMIENTO
Los servicios de fisioterapia y rehabilitación pueden incluir: terapia manual, ejercicio terapéutico, electroterapia, ultrasonido, termoterapia, crioterapia, reeducación postural y otras técnicas de rehabilitación física determinadas por el terapeuta tratante.

BENEFICIOS ESPERADOS
Reducción del dolor, mejora de la movilidad y función articular, fortalecimiento muscular, prevención de lesiones y mejora de la calidad de vida.

POSIBLES RIESGOS
Como en toda intervención terapéutica, pueden presentarse molestias temporales, sensibilidad muscular post-sesión o reacciones mínimas a técnicas específicas. El terapeuta evaluará cada caso y adaptará el tratamiento.

ALTERNATIVAS
El paciente tiene derecho a conocer alternativas de tratamiento disponibles y puede solicitar una segunda opinión antes de iniciar.

DERECHOS DEL PACIENTE
• Recibir información clara sobre su diagnóstico y plan de tratamiento
• Revocar este consentimiento en cualquier momento de forma verbal o escrita
• Ser tratado con respeto y dignidad
• Acceder a su expediente clínico

Al firmar, confirmo que he leído y comprendido la información anterior, que se ha respondido a mis preguntas y que otorgo voluntariamente mi consentimiento para iniciar el tratamiento de fisioterapia.

FisioVida Servicios Clínicos S.A. de C.V. | +52 55 1234 5678'],

            ['key' => 'image_consent_text', 'group' => 'compliance', 'label' => 'Consentimiento de imágenes', 'is_public' => false, 'value' =>
'CONSENTIMIENTO PARA USO DE IMÁGENES CLÍNICAS
FisioVida — Fisioterapia y Rehabilitación

FINALIDAD
FisioVida puede tomar fotografías o videos durante las sesiones de evaluación y seguimiento con el objetivo exclusivo de:
• Documentar la evolución del tratamiento dentro del expediente clínico
• Apoyar al terapeuta en la planificación y ajuste del plan de rehabilitación
• Fines de enseñanza o investigación interna (solo con anonimización y consentimiento adicional)

ALCANCE Y LIMITACIONES
• Las imágenes clínicas son estrictamente confidenciales y forman parte del expediente clínico.
• No se utilizarán para publicidad, redes sociales ni difusión pública sin autorización adicional y expresa.
• No serán transferidas a terceros sin consentimiento.

ALMACENAMIENTO Y SEGURIDAD
Las imágenes se almacenan en servidores seguros bajo las mismas medidas de protección que el expediente clínico.

REVOCACIÓN
Puede revocar este consentimiento en cualquier momento, lo que implicará la eliminación de las imágenes de su expediente, sin afectar la continuidad del tratamiento.

Al firmar este documento, otorga su consentimiento libre e informado para la captación y uso de imágenes clínicas con las finalidades descritas.

FisioVida | privacidad@fisioclinica.mx | +52 55 1234 5678'],

            ['key' => 'minor_consent_text', 'group' => 'compliance', 'label' => 'Consentimiento para menores de edad', 'is_public' => false, 'value' =>
'CONSENTIMIENTO DE TUTOR O REPRESENTANTE LEGAL PARA TRATAMIENTO DE MENOR DE EDAD
FisioVida — Fisioterapia y Rehabilitación

El suscrito, en mi carácter de padre, madre, tutor legal o representante del menor de edad indicado en este documento, otorgo el presente consentimiento informado para la prestación de servicios de fisioterapia y rehabilitación.

DECLARACIONES
• Soy el responsable legal del menor y tengo capacidad legal para otorgar este consentimiento.
• He sido informado sobre el diagnóstico, plan de tratamiento, beneficios esperados y posibles riesgos.
• Autorizo al personal de FisioVida a realizar las evaluaciones y tratamientos de fisioterapia necesarios.
• Entiendo que puedo revocar este consentimiento en cualquier momento.

DERECHOS DEL MENOR Y TUTOR
• Recibir información clara y comprensible sobre el tratamiento
• Ser informado de cualquier cambio en el plan terapéutico
• Acceder al expediente clínico del menor
• Solicitar segunda opinión

DATOS DEL TRATAMIENTO
Las mismas condiciones descritas en el Consentimiento de Tratamiento aplican para el menor. El tutor asume la responsabilidad de informar al menor sobre el tratamiento de acuerdo con su edad y madurez.

Al firmar, confirmo que actúo en representación legal del menor, que comprendo el tratamiento propuesto y que otorgo mi consentimiento de manera libre y voluntaria.

FisioVida Servicios Clínicos S.A. de C.V. | privacidad@fisioclinica.mx'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting + ['type' => 'string', 'description' => null]
            );
        }
    }
}
