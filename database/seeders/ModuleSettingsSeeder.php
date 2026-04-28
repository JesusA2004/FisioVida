<?php

namespace Database\Seeders;

use App\Models\ModuleSetting;
use Illuminate\Database\Seeder;

class ModuleSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['module' => 'dashboard', 'label' => 'Dashboard'],
            ['module' => 'pacientes', 'label' => 'Pacientes'],
            ['module' => 'agenda', 'label' => 'Agenda'],
            ['module' => 'sesiones', 'label' => 'Sesiones'],
            ['module' => 'ejercicios', 'label' => 'Ejercicios'],
            ['module' => 'archivos', 'label' => 'Archivos'],
            ['module' => 'pagos', 'label' => 'Pagos'],
            ['module' => 'actividades', 'label' => 'Actividades'],
            ['module' => 'usuarios', 'label' => 'Usuarios'],
            ['module' => 'roles', 'label' => 'Roles'],
            ['module' => 'permisos', 'label' => 'Permisos'],
            ['module' => 'reportes', 'label' => 'Reportes'],
            ['module' => 'logs', 'label' => 'Logs'],
            ['module' => 'configuracion', 'label' => 'Configuración'],
        ];

        foreach ($modules as $index => $module) {
            ModuleSetting::query()->updateOrCreate(
                ['module' => $module['module']],
                $module + [
                    'enabled' => true,
                    'sort_order' => $index + 1,
                    'description' => null,
                    'settings' => null,
                ]
            );
        }
    }
}
