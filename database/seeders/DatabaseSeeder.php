<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            RolesSeeder::class,
            SystemSettingsSeeder::class,
            ModuleSettingsSeeder::class,
            UsuariosDemoSeeder::class,
            PacientesDemoSeeder::class,
            EjerciciosDemoSeeder::class,
            CitasDemoSeeder::class,
            SesionesDemoSeeder::class,
            PagosDemoSeeder::class,
            ActividadesDemoSeeder::class,
            AppointmentRequestsDemoSeeder::class,
            CumplimientoDemoSeeder::class,
        ]);
    }
}
