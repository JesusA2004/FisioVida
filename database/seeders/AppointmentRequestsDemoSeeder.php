<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AppointmentRequestsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('appointment_requests')) {
            return;
        }

        $sofiaPersonaId = DB::table('users')->where('email', 'paciente@demo.com')->value('persona_id');
        if (! $sofiaPersonaId) {
            return;
        }

        $sofiaUserId = DB::table('users')->where('email', 'paciente@demo.com')->value('id');
        $recepcionId = DB::table('users')->where('email', 'recepcion@demo.com')->value('id');

        // Pending request — visible en portal y en lista admin
        DB::table('appointment_requests')->updateOrInsert(
            [
                'patient_persona_id' => $sofiaPersonaId,
                'preferred_date' => now()->addDays(7)->toDateString(),
            ],
            [
                'preferred_time' => '10:00',
                'reason' => 'Dolor lumbar recurrente, quiero retomar las sesiones.',
                'notes' => 'Prefiero en la mañana si es posible.',
                'status' => 'pending',
                'rejection_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_appointment_id' => null,
                'created_by' => $sofiaUserId,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ]
        );

        // Rejected request — con motivo
        DB::table('appointment_requests')->updateOrInsert(
            [
                'patient_persona_id' => $sofiaPersonaId,
                'preferred_date' => now()->subDays(10)->toDateString(),
            ],
            [
                'preferred_time' => '09:00',
                'reason' => 'Evaluación inicial para dolor de hombro.',
                'notes' => null,
                'status' => 'rejected',
                'rejection_reason' => 'No tenemos disponibilidad esa fecha. Por favor solicita otra.',
                'reviewed_by' => $recepcionId,
                'reviewed_at' => now()->subDays(9)->toDateTimeString(),
                'approved_appointment_id' => null,
                'created_by' => $sofiaUserId,
                'created_at' => now()->subDays(11),
                'updated_at' => now()->subDays(9),
            ]
        );

        // File demo: visible_to_patient = true
        if (Schema::hasTable('files') && Schema::hasColumn('files', 'visible_to_patient')) {
            $hasSessionId = Schema::hasColumn('files', 'session_id');
            $uploaderUserId = DB::table('users')->where('email', 'terapeuta@demo.com')->value('id');

            // Create placeholder files on disk so portal "Ver" doesn't 404
            $demoDir = storage_path('app/private/demo');
            if (! is_dir($demoDir)) {
                mkdir($demoDir, 0755, true);
            }
            $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 612 792]>>endobj\n%%EOF";
            foreach (['plan-ejercicios-sofia.pdf', 'notas-internas-sofia.pdf'] as $fname) {
                $filePath = $demoDir . '/' . $fname;
                if (! file_exists($filePath)) {
                    file_put_contents($filePath, $pdfContent);
                }
            }

            $fileBase = [
                'disk'               => 'private',
                'file_type'          => 'document',
                'mime'               => 'application/pdf',
                'uploaded_by'        => $uploaderUserId,
                'patient_persona_id' => $sofiaPersonaId,
            ];

            DB::table('files')->updateOrInsert(
                ['patient_persona_id' => $sofiaPersonaId, 'original_name' => 'plan-ejercicios-sofia.pdf'],
                array_merge($fileBase, [
                    'path'               => 'demo/plan-ejercicios-sofia.pdf',
                    'size_bytes'         => 102400,
                    'visible_to_patient' => true,
                    'created_at'         => now()->subDays(5),
                    'updated_at'         => now()->subDays(5),
                ])
            );

            // File NOT visible to patient — intentionally hidden from portal
            DB::table('files')->updateOrInsert(
                ['patient_persona_id' => $sofiaPersonaId, 'original_name' => 'notas-internas-sofia.pdf'],
                array_merge($fileBase, [
                    'path'               => 'demo/notas-internas-sofia.pdf',
                    'size_bytes'         => 51200,
                    'visible_to_patient' => false,
                    'created_at'         => now()->subDays(3),
                    'updated_at'         => now()->subDays(3),
                ])
            );
        }
    }
}
