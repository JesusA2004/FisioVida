<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrador', 'slug' => 'administrador', 'description' => 'Acceso completo al sistema.', 'status' => 'active'],
            ['name' => 'Recepción', 'slug' => 'recepcion', 'description' => 'Gestión de agenda y pacientes.', 'status' => 'active'],
            ['name' => 'Terapeuta', 'slug' => 'terapeuta', 'description' => 'Atención clínica y sesiones.', 'status' => 'active'],
            ['name' => 'Cobranza', 'slug' => 'cobranza', 'description' => 'Seguimiento de pagos e ingresos.', 'status' => 'active'],
            ['name' => 'Dirección', 'slug' => 'direccion', 'description' => 'Consulta de indicadores y reportes.', 'status' => 'active'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
