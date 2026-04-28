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
    }
}
