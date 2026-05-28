<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PagosDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        $statuses = ['pending', 'paid', 'paid', 'paid'];
        $patients = DB::table('personas')
            ->whereIn('tipo', ['paciente', 'ambos'])
            ->whereNull('deleted_at')
            ->pluck('id')
            ->values();

        if ($patients->isEmpty()) {
            return;
        }

        $concepts = [
            'Sesión de fisioterapia', 'Evaluación inicial', 'Sesión de rehabilitación',
            'Terapia manual', 'Electroterapia', 'Ultrasonido terapéutico',
        ];

        for ($i = 0; $i < 20; $i++) {
            $status = $statuses[$i % count($statuses)];
            $createdAt = now()->subDays($i);
            $patientId = $patients[$i % $patients->count()];

            DB::table('payments')->updateOrInsert(
                ['provider_payment_id' => 'DEMO-PAY-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'provider' => 'manual',
                    'amount' => 450 + ($i * 35),
                    'currency' => 'MXN',
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? $createdAt->copy()->addHours(2) : null,
                    'reference' => 'FV-REC-'.str_pad((string) ($i + 100), 5, '0', STR_PAD_LEFT),
                    'concept' => $concepts[$i % count($concepts)],
                    'patient_persona_id' => $patientId,
                    'notes' => 'Pago demo de servicios de rehabilitación.',
                    'created_at' => $createdAt,
                    'updated_at' => now(),
                ]
            );
        }
    }
}
