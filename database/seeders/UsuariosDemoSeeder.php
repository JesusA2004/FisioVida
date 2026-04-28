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
        ];

        foreach ($demoUsers as $item) {
            $persona = Persona::query()->updateOrCreate(
                [
                    'nombres' => $item['persona']['nombres'],
                    'apellido_paterno' => $item['persona']['apellido_paterno'],
                    'apellido_materno' => $item['persona']['apellido_materno'],
                ],
                [
                    'tipo' => 'staff',
                    'status' => 'active',
                    'telefono' => $item['persona']['telefono'],
                    'direccion' => 'Sucursal Demo FisioVida, Ciudad de México',
                    'notas' => 'DEMO_USER:'.$item['email'],
                ]
            );

            $user = User::query()->updateOrCreate(
                ['email' => $item['email']],
                [
                    'persona_id' => $persona->id,
                    'name' => $item['name'],
                    'password' => Hash::make('password'),
                    'status' => 'active',
                    'is_super_admin' => (bool) ($item['is_super_admin'] ?? false),
                    'mod_agenda' => true,
                    'mod_pacientes' => true,
                    'mod_sesiones' => true,
                    'mod_ejercicios' => true,
                    'mod_archivos' => true,
                    'mod_reportes' => true,
                    'mod_cobranza' => true,
                    'mod_config' => true,
                ]
            );

            $role = Role::query()->where('slug', $item['role'])->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        }
    }
}
