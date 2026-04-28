<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SesionesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('therapy_sessions') || ! Schema::hasTable('appointments')) {
            return;
        }

        $appointments = DB::table('appointments')->orderBy('start_at')->get();

        foreach ($appointments->take(18) as $index => $appointment) {
            $sessionDate = now()->subDays(18 - $index)->toDateString();

            DB::table('therapy_sessions')->updateOrInsert(
                [
                    'appointment_id' => $appointment->id,
                    'patient_persona_id' => $appointment->patient_persona_id,
                    'therapist_user_id' => $appointment->therapist_user_id,
                    'session_date' => $sessionDate,
                ],
                [
                    'subjective' => 'Refiere dolor moderado al final de la jornada laboral.',
                    'objective' => 'Disminución leve de rango articular y tensión muscular.',
                    'assessment' => 'Evolución favorable con respuesta positiva al tratamiento.',
                    'plan' => 'Continuar fortalecimiento progresivo y educación postural.',
                    'pain_scale' => ($index % 6) + 2,
                    'notes' => 'Sesión demo con evolución funcional documentada.',
                    'created_at' => now()->subDays(20),
                    'updated_at' => now(),
                ]
            );
        }

        if (! Schema::hasTable('session_exercises') || ! Schema::hasTable('exercises')) {
            return;
        }

        $sessionIds = DB::table('therapy_sessions')->orderBy('id')->pluck('id');
        $exerciseIds = DB::table('exercises')->where('is_active', true)->orderBy('id')->pluck('id');

        if ($sessionIds->isEmpty() || $exerciseIds->isEmpty()) {
            return;
        }

        foreach ($sessionIds->take(14) as $sIndex => $sessionId) {
            for ($j = 0; $j < 3; $j++) {
                $exerciseId = $exerciseIds[($sIndex + $j) % $exerciseIds->count()];

                DB::table('session_exercises')->updateOrInsert(
                    ['session_id' => $sessionId, 'exercise_id' => $exerciseId],
                    [
                        'sets' => 2 + ($j % 2),
                        'reps' => 10 + ($j * 2),
                        'seconds' => 30 + ($j * 15),
                        'notes' => 'Frecuencia: 4 veces por semana. Instrucción: realizar sin dolor y con respiración controlada.',
                    ]
                );
            }
        }
    }
}
