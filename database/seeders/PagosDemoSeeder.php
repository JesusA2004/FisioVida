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

        $statuses = ['pending', 'paid', 'failed', 'refunded'];

        for ($i = 0; $i < 20; $i++) {
            $status = $statuses[$i % count($statuses)];
            $createdAt = now()->subDays($i);

            DB::table('payments')->updateOrInsert(
                ['provider_payment_id' => 'DEMO-PAY-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'provider' => $i % 2 === 0 ? 'stripe' : 'mercadopago',
                    'amount' => 450 + ($i * 35),
                    'currency' => 'MXN',
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? $createdAt->copy()->addHours(2) : null,
                    'reference' => 'FV-REC-'.str_pad((string) ($i + 100), 5, '0', STR_PAD_LEFT),
                    'notes' => 'Pago demo de servicios de rehabilitación.',
                    'created_at' => $createdAt,
                    'updated_at' => now(),
                ]
            );
        }
    }
}
