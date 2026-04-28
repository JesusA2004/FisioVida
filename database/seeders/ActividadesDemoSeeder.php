<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ActividadesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('activities') || ! Schema::hasTable('users')) {
            return;
        }

        $users = DB::table('users')->pluck('id');
        $patients = Schema::hasTable('personas')
            ? DB::table('personas')->whereIn('tipo', ['paciente', 'ambos'])->pluck('id')
            : collect();
        $appointments = Schema::hasTable('appointments') ? DB::table('appointments')->pluck('id') : collect();
        $sessions = Schema::hasTable('therapy_sessions') ? DB::table('therapy_sessions')->pluck('id') : collect();
        $payments = Schema::hasTable('payments') ? DB::table('payments')->pluck('id') : collect();

        if ($users->isEmpty()) {
            return;
        }

        $statuses = ['pending', 'in_progress', 'completed', 'cancelled', 'overdue'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        for ($i = 0; $i < 16; $i++) {
            $status = $statuses[$i % count($statuses)];
            $dueDate = now()->addDays(($i % 6) - 2);

            DB::table('activities')->updateOrInsert(
                ['title' => 'Actividad demo #'.($i + 1)],
                [
                    'description' => 'Seguimiento administrativo y clínico para flujo demo de FisioVida.',
                    'responsible_user_id' => $users[$i % $users->count()],
                    'patient_persona_id' => $patients->isEmpty() ? null : $patients[$i % $patients->count()],
                    'appointment_id' => $appointments->isEmpty() ? null : $appointments[$i % $appointments->count()],
                    'therapy_session_id' => $sessions->isEmpty() ? null : $sessions[$i % $sessions->count()],
                    'payment_id' => $payments->isEmpty() ? null : $payments[$i % $payments->count()],
                    'priority' => $priorities[$i % count($priorities)],
                    'status' => $status,
                    'due_date' => $dueDate,
                    'completed_at' => in_array($status, ['completed', 'cancelled'], true) ? now()->subDays(1) : null,
                    'completed_by' => in_array($status, ['completed', 'cancelled'], true) ? $users[0] : null,
                    'created_by' => $users[0],
                    'updated_by' => $users[0],
                    'created_at' => now()->subDays(10),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
