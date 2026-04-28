<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PacientesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('personas')) {
            return;
        }

        $columns = Schema::getColumnListing('personas');
        $allow = array_flip($columns);

        $rows = [
            ['nombre' => 'Sofía', 'ap' => 'Ramírez', 'am' => 'Lozano', 'sexo' => 'F'],
            ['nombre' => 'Daniel', 'ap' => 'Ortega', 'am' => 'Muñoz', 'sexo' => 'M'],
            ['nombre' => 'Fernanda', 'ap' => 'Castillo', 'am' => 'Neri', 'sexo' => 'F'],
            ['nombre' => 'Luis', 'ap' => 'Mora', 'am' => 'Patiño', 'sexo' => 'M'],
            ['nombre' => 'Paola', 'ap' => 'Santos', 'am' => 'Franco', 'sexo' => 'F'],
            ['nombre' => 'Emilio', 'ap' => 'Guerrero', 'am' => 'Rivas', 'sexo' => 'M'],
            ['nombre' => 'Valeria', 'ap' => 'Ibarra', 'am' => 'Soto', 'sexo' => 'F'],
            ['nombre' => 'Hugo', 'ap' => 'Delgado', 'am' => 'Ponce', 'sexo' => 'M'],
            ['nombre' => 'Andrea', 'ap' => 'Fuentes', 'am' => 'Peña', 'sexo' => 'F'],
            ['nombre' => 'Roberto', 'ap' => 'Navarro', 'am' => 'Salinas', 'sexo' => 'M'],
            ['nombre' => 'Karla', 'ap' => 'Márquez', 'am' => 'Vargas', 'sexo' => 'F'],
            ['nombre' => 'Javier', 'ap' => 'León', 'am' => 'Serrano', 'sexo' => 'M'],
            ['nombre' => 'Natalia', 'ap' => 'Cortés', 'am' => 'Valle', 'sexo' => 'F'],
            ['nombre' => 'Raúl', 'ap' => 'Espinoza', 'am' => 'Ávila', 'sexo' => 'M'],
            ['nombre' => 'Mónica', 'ap' => 'Cuevas', 'am' => 'Herrera', 'sexo' => 'F'],
        ];

        foreach ($rows as $i => $p) {
            $payload = [
                'tipo' => 'paciente',
                'status' => $i % 7 === 0 ? 'inactive' : 'active',
                'nombres' => $p['nombre'],
                'apellido_paterno' => $p['ap'],
                'apellido_materno' => $p['am'],
                'fecha_nacimiento' => now()->subYears(20 + $i)->subDays($i * 9)->toDateString(),
                'sexo' => $p['sexo'],
                'telefono' => '55320'.str_pad((string) ($i + 1000), 4, '0', STR_PAD_LEFT),
                'direccion' => 'Calle '.($i + 10).' Colonia Centro, CDMX',
                'contacto_emergencia_nombre' => 'Familiar '.$p['ap'],
                'contacto_emergencia_telefono' => '55440'.str_pad((string) ($i + 2000), 4, '0', STR_PAD_LEFT),
                'notas' => 'Paciente demo con seguimiento de rehabilitación musculoesquelética.',
                'created_at' => now()->subDays(40 - $i),
                'updated_at' => now()->subDays(5),
            ];

            if (isset($allow['email'])) {
                $payload['email'] = strtolower($p['nombre']).'.'.strtolower($p['ap']).'@demo-fv.com';
            }

            $record = array_intersect_key($payload, $allow);

            DB::table('personas')->updateOrInsert(
                ['nombres' => $p['nombre'], 'apellido_paterno' => $p['ap'], 'apellido_materno' => $p['am']],
                $record
            );
        }
    }
}
