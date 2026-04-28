<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EjerciciosDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('exercises')) {
            return;
        }

        $items = [
            ['name' => 'Estiramiento cervical', 'description' => 'Movilización suave para cuello con control respiratorio.', 'video_url' => 'https://youtu.be/demo-cervical'],
            ['name' => 'Movilidad de hombro', 'description' => 'Ejercicio activo-asistido para mejorar rango de movimiento.', 'video_url' => 'https://youtu.be/demo-hombro'],
            ['name' => 'Fortalecimiento lumbar', 'description' => 'Activación de core y estabilización lumbar.', 'video_url' => null],
            ['name' => 'Respiración diafragmática', 'description' => 'Control de patrón respiratorio y relajación.', 'video_url' => null],
            ['name' => 'Estiramiento de isquiotibiales', 'description' => 'Trabajo de flexibilidad posterior de muslo.', 'video_url' => 'https://youtu.be/demo-isquios'],
            ['name' => 'Puente de glúteos', 'description' => 'Fortalecimiento de cadena posterior.', 'video_url' => null],
            ['name' => 'Sentadilla asistida', 'description' => 'Reeducación funcional de miembros inferiores.', 'video_url' => null],
            ['name' => 'Propiocepción de tobillo', 'description' => 'Control neuromuscular en apoyo unipodal.', 'video_url' => 'https://youtu.be/demo-tobillo'],
            ['name' => 'Movilidad torácica', 'description' => 'Rotaciones torácicas en cuadrupedia.', 'video_url' => null],
            ['name' => 'Fortalecimiento escapular', 'description' => 'Retracción escapular con banda elástica.', 'video_url' => 'https://youtu.be/demo-escapular'],
        ];

        foreach ($items as $index => $exercise) {
            DB::table('exercises')->updateOrInsert(
                ['name' => $exercise['name']],
                [
                    'description' => $exercise['description'],
                    'video_url' => $exercise['video_url'],
                    'is_active' => $index !== 9,
                    'updated_at' => now(),
                    'created_at' => now()->subDays(20),
                ]
            );
        }
    }
}
