<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CitasDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('appointments') || ! Schema::hasTable('personas') || ! Schema::hasTable('users')) {
            return;
        }

        $therapistRole = Role::query()->where('slug', 'terapeuta')->first();
        $therapists = $therapistRole
            ? User::query()->whereHas('roles', fn ($q) => $q->where('roles.id', $therapistRole->id))->pluck('id')->values()
            : User::query()->whereIn('email', ['terapeuta@demo.com', 'terapeuta.ana@demo.com', 'terapeuta.ivan@demo.com'])->pluck('id')->values();

        $patients = DB::table('personas')->whereIn('tipo', ['paciente', 'ambos'])->whereNull('deleted_at')->pluck('id')->values();
        $creator = User::query()->where('email', 'recepcion@demo.com')->value('id') ?: User::query()->value('id');

        if ($therapists->isEmpty() || $patients->isEmpty()) {
            return;
        }

        $statuses = ['scheduled', 'confirmed', 'arrived', 'no_show', 'cancelled', 'done'];

        for ($i = 0; $i < 24; $i++) {
            $start = now()->startOfDay()->addDays($i - 8)->addHours(8 + ($i % 8));
            $status = $statuses[$i % count($statuses)];
            $patientId = $patients[$i % $patients->count()];
            $therapistId = $therapists[$i % $therapists->count()];

            DB::table('appointments')->updateOrInsert(
                [
                    'patient_persona_id' => $patientId,
                    'therapist_user_id' => $therapistId,
                    'start_at' => $start,
                ],
                [
                    'end_at' => $start->copy()->addMinutes(60),
                    'status' => $status,
                    'notes' => 'Cita demo '.($i + 1).' para seguimiento fisioterapéutico.',
                    'created_by' => $creator,
                    'created_at' => now()->subDays(20),
                    'updated_at' => now(),
                ]
            );
        }

        // Citas garantizadas HOY para el terapeuta principal — crítico para Mi Jornada demo
        $mainTherapist = User::query()->where('email', 'terapeuta@demo.com')->value('id')
            ?: $therapists->first();

        // Paciente 1 (índice 0): cita arrived — para demo de sesión inline
        $demoPatient1 = $patients[0];
        // Paciente 2 (índice 1): cita confirmed — para ver flujo de confirmación
        $demoPatient2 = $patients[1 % $patients->count()];
        // Paciente 3 (índice 2): cita scheduled
        $demoPatient3 = $patients[2 % $patients->count()];

        $todayCitas = [
            ['patient_id' => $demoPatient1, 'hour' => 9, 'status' => 'arrived',
             'notes' => 'Paciente en sala — listo para atención. Lumbalgia crónica, 3ra sesión.'],
            ['patient_id' => $demoPatient2, 'hour' => 10, 'status' => 'confirmed',
             'notes' => 'Confirmada. Rehabilitación post-quirúrgica rodilla derecha.'],
            ['patient_id' => $demoPatient3, 'hour' => 11, 'status' => 'scheduled',
             'notes' => 'Primera evaluación. Cervicalgia por postura laboral.'],
        ];

        foreach ($todayCitas as $tc) {
            $start = now()->startOfDay()->addHours($tc['hour']);
            DB::table('appointments')->updateOrInsert(
                [
                    'patient_persona_id' => $tc['patient_id'],
                    'therapist_user_id'  => $mainTherapist,
                    'start_at'           => $start,
                ],
                [
                    'end_at'     => $start->copy()->addMinutes(60),
                    'status'     => $tc['status'],
                    'notes'      => $tc['notes'],
                    'created_by' => $creator,
                    'created_at' => now()->subDays(1),
                    'updated_at' => now(),
                ]
            );
        }

        // Cita futura para Sofía (paciente demo del portal) — para que /mi-portal muestre próxima cita
        $sofiaPersonaId = DB::table('users')->where('email', 'paciente@demo.com')->value('persona_id');
        if ($sofiaPersonaId) {
            $nextWeek = now()->startOfDay()->addDays(5)->addHours(10);
            DB::table('appointments')->updateOrInsert(
                [
                    'patient_persona_id' => $sofiaPersonaId,
                    'therapist_user_id'  => $mainTherapist,
                    'start_at'           => $nextWeek,
                ],
                [
                    'end_at'     => $nextWeek->copy()->addMinutes(60),
                    'status'     => 'scheduled',
                    'notes'      => 'Sesión de seguimiento — continuación plan de fortalecimiento lumbar.',
                    'created_by' => $creator,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
