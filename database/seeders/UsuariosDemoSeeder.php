<?php

namespace Database\Seeders;

use App\Models\Persona;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $demoUsers = [
            [
                'email' => 'admin@demo.com',
                'name' => 'Mariana Torres',
                'role' => 'administrador',
                'persona' => ['nombres' => 'Mariana', 'apellido_paterno' => 'Torres', 'apellido_materno' => 'Ríos', 'telefono' => '5511110001'],
                'is_super_admin' => true,
            ],
            [
                'email' => 'recepcion@demo.com',
                'name' => 'Lucía Méndez',
                'role' => 'recepcion',
                'persona' => ['nombres' => 'Lucía', 'apellido_paterno' => 'Méndez', 'apellido_materno' => 'Salas', 'telefono' => '5511110002'],
            ],
            [
                'email' => 'terapeuta@demo.com',
                'name' => 'Carlos Pineda',
                'role' => 'terapeuta',
                'persona' => ['nombres' => 'Carlos', 'apellido_paterno' => 'Pineda', 'apellido_materno' => 'Cruz', 'telefono' => '5511110003'],
            ],
            [
                'email' => 'cobranza@demo.com',
                'name' => 'Diana Campos',
                'role' => 'cobranza',
                'persona' => ['nombres' => 'Diana', 'apellido_paterno' => 'Campos', 'apellido_materno' => 'Luna', 'telefono' => '5511110004'],
            ],
            [
                'email' => 'direccion@demo.com',
                'name' => 'Jorge Salgado',
                'role' => 'direccion',
                'persona' => ['nombres' => 'Jorge', 'apellido_paterno' => 'Salgado', 'apellido_materno' => 'Vega', 'telefono' => '5511110005'],
            ],
            [
                'email' => 'terapeuta.ana@demo.com',
                'name' => 'Ana Belén Ruiz',
                'role' => 'terapeuta',
                'persona' => ['nombres' => 'Ana Belén', 'apellido_paterno' => 'Ruiz', 'apellido_materno' => 'Duarte', 'telefono' => '5511110006'],
            ],
            [
                'email' => 'terapeuta.ivan@demo.com',
                'name' => 'Iván Solís',
                'role' => 'terapeuta',
                'persona' => ['nombres' => 'Iván', 'apellido_paterno' => 'Solís', 'apellido_materno' => 'Nava', 'telefono' => '5511110007'],
            ],
            [
                'email' => 'paciente@demo.com',
                'name' => 'Sofía Ramírez',
                'role' => 'paciente',
                'persona_tipo' => 'paciente',
                'persona' => [
                    'nombres' => 'Sofía',
                    'apellido_paterno' => 'Ramírez',
                    'apellido_materno' => 'Lozano',
                    'telefono' => '5532001000',
                    'fecha_nacimiento' => '1993-04-15',
                    'sexo' => 'F',
                    'direccion' => 'Av. Insurgentes Sur 1234, Col. Del Valle, CDMX',
                    'notas' => 'Paciente demo portal. Rehabilitación musculoesquelética por lumbalgia crónica.',
                ],
            ],
        ];

        foreach ($demoUsers as $item) {
            $tipo = $item['persona_tipo'] ?? 'staff';
            $personaData = array_merge([
                'tipo' => $tipo,
                'status' => 'active',
                'telefono' => $item['persona']['telefono'],
                'direccion' => $item['persona']['direccion'] ?? 'Sucursal Demo FisioVida, Ciudad de México',
                'notas' => $item['persona']['notas'] ?? 'DEMO_USER:'.$item['email'],
            ], array_intersect_key($item['persona'], array_flip(['fecha_nacimiento', 'sexo'])));

            $persona = Persona::query()->updateOrCreate(
                [
                    'nombres' => $item['persona']['nombres'],
                    'apellido_paterno' => $item['persona']['apellido_paterno'],
                    'apellido_materno' => $item['persona']['apellido_materno'],
                ],
                $personaData
            );

            // Paciente no debe tener acceso legacy via mod_*
            $isPatientRole = ($item['role'] === 'paciente');

            $user = User::query()->updateOrCreate(
                ['email' => $item['email']],
                [
                    'persona_id' => $persona->id,
                    'name' => $item['name'],
                    'password' => Hash::make('password'),
                    'status' => 'active',
                    'is_super_admin' => (bool) ($item['is_super_admin'] ?? false),
                    'mod_agenda' => ! $isPatientRole,
                    'mod_pacientes' => ! $isPatientRole,
                    'mod_sesiones' => ! $isPatientRole,
                    'mod_ejercicios' => ! $isPatientRole,
                    'mod_archivos' => ! $isPatientRole,
                    'mod_reportes' => ! $isPatientRole,
                    'mod_cobranza' => ! $isPatientRole,
                    'mod_config' => ! $isPatientRole,
                ]
            );

            $role = Role::query()->where('slug', $item['role'])->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        }
    }
}
