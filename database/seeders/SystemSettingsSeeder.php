<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'clinic_name', 'value' => 'FisioVida Demo', 'group' => 'clinic', 'label' => 'Nombre de clínica', 'is_public' => true],
            ['key' => 'clinic_logo', 'value' => '/ogLogo.png', 'group' => 'clinic', 'label' => 'Logo', 'is_public' => true],
            ['key' => 'clinic_phone', 'value' => '+52 55 1234 5678', 'group' => 'clinic', 'label' => 'Teléfono', 'is_public' => false],
            ['key' => 'clinic_email', 'value' => 'contacto@fisio.demo', 'group' => 'clinic', 'label' => 'Email', 'is_public' => false],
            ['key' => 'clinic_address', 'value' => 'Av. Reforma 100, CDMX', 'group' => 'clinic', 'label' => 'Dirección', 'is_public' => false],
            ['key' => 'primary_color', 'value' => '#3B82F6', 'group' => 'appearance', 'label' => 'Color primario', 'is_public' => true],
            ['key' => 'secondary_color', 'value' => '#22C55E', 'group' => 'appearance', 'label' => 'Color secundario', 'is_public' => true],
            ['key' => 'accent_color', 'value' => '#F59E0B', 'group' => 'appearance', 'label' => 'Color acento', 'is_public' => true],
            ['key' => 'default_currency', 'value' => 'MXN', 'group' => 'general', 'label' => 'Moneda', 'is_public' => false],
            ['key' => 'appointment_default_duration', 'value' => '60', 'group' => 'general', 'label' => 'Duración de cita', 'is_public' => false],
            ['key' => 'demo_mode', 'value' => '1', 'group' => 'general', 'label' => 'Modo demo', 'is_public' => true],
            ['key' => 'dark_mode_enabled', 'value' => '1', 'group' => 'general', 'label' => 'Modo oscuro habilitado', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting + ['type' => 'string', 'description' => null]
            );
        }
    }
}
