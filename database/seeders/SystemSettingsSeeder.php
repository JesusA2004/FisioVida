<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Datos de clínica
            ['key' => 'clinic_name',    'value' => 'FisioVida Demo',        'group' => 'clinic',      'label' => 'Nombre de clínica',        'is_public' => true],
            ['key' => 'clinic_logo',    'value' => '/ogLogo.png',           'group' => 'clinic',      'label' => 'Logo',                     'is_public' => true],
            ['key' => 'clinic_phone',   'value' => '+52 55 1234 5678',      'group' => 'clinic',      'label' => 'Teléfono',                 'is_public' => false],
            ['key' => 'clinic_email',   'value' => 'contacto@fisio.demo',   'group' => 'clinic',      'label' => 'Email',                    'is_public' => false],
            ['key' => 'clinic_address', 'value' => 'Av. Reforma 100, CDMX', 'group' => 'clinic',     'label' => 'Dirección',                'is_public' => false],

            // Colores de tema — alineados con los tokens CSS de app.css
            ['key' => 'primary_color',            'value' => '#059AB2', 'group' => 'appearance', 'label' => 'Color primario',          'is_public' => true],
            ['key' => 'primary_hover_color',      'value' => '#047A8E', 'group' => 'appearance', 'label' => 'Hover de botones',        'is_public' => true],
            ['key' => 'primary_foreground_color', 'value' => '#FFFFFF', 'group' => 'appearance', 'label' => 'Texto en botones',        'is_public' => true],
            ['key' => 'app_background_color',     'value' => '#EDF2F7', 'group' => 'appearance', 'label' => 'Fondo general',           'is_public' => true],
            ['key' => 'card_background_color',    'value' => '#FFFFFF', 'group' => 'appearance', 'label' => 'Fondo de tarjetas',       'is_public' => true],
            ['key' => 'sidebar_background_color', 'value' => '#FFFFFF', 'group' => 'appearance', 'label' => 'Fondo del menú lateral',  'is_public' => true],

            // Generales
            ['key' => 'default_currency',            'value' => 'MXN', 'group' => 'general', 'label' => 'Moneda',          'is_public' => false],
            ['key' => 'appointment_default_duration', 'value' => '60', 'group' => 'general', 'label' => 'Duración de cita', 'is_public' => false],
            ['key' => 'demo_mode',                   'value' => '1',  'group' => 'general', 'label' => 'Modo demo',         'is_public' => true],
            ['key' => 'dark_mode_enabled',           'value' => '0',  'group' => 'general', 'label' => 'Modo oscuro',       'is_public' => false],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting + ['type' => 'string', 'description' => null]
            );
        }
    }
}
