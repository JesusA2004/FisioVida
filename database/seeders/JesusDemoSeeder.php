<?php

namespace Database\Seeders;

use App\Models\Persona;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class JesusDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Persona ──────────────────────────────────────────────────────────
        $persona = Persona::query()->updateOrCreate(
            [
                'nombres'           => 'Jesús Arturo',
                'apellido_paterno'  => 'Arizméndi',
                'apellido_materno'  => 'Maya',
            ],
            [
                'tipo'              => 'paciente',
                'status'            => 'active',
                'fecha_nacimiento'  => '1998-06-10',
                'sexo'              => 'M',
                'telefono'          => '5512345678',
                'direccion'         => 'Av. Insurgentes Sur 1234, Col. Del Valle, CDMX, C.P. 03100',
                'notas'             => 'Paciente demo. Diagnóstico: lumbalgia crónica + tendinitis de hombro derecho. Tratamiento activo.',
            ]
        );

        // ── User ─────────────────────────────────────────────────────────────
        $user = User::query()->updateOrCreate(
            ['email' => 'jesusarizmendimaya@gmail.com'],
            [
                'persona_id'     => $persona->id,
                'name'           => 'Jesús Arturo Arizméndi Maya',
                'password'       => Hash::make('password'),
                'status'         => 'active',
                'is_super_admin' => false,
                'mod_agenda'     => false,
                'mod_pacientes'  => false,
                'mod_sesiones'   => false,
                'mod_ejercicios' => false,
                'mod_archivos'   => false,
                'mod_reportes'   => false,
                'mod_cobranza'   => false,
                'mod_config'     => false,
            ]
        );

        $pacienteRole = Role::query()->where('slug', 'paciente')->first();
        if ($pacienteRole) {
            $user->roles()->syncWithoutDetaching([$pacienteRole->id]);
        }

        $personaId = $persona->id;
        $userId    = $user->id;

        // ── Terapeuta principal ───────────────────────────────────────────────
        $therapistId = DB::table('users')->where('email', 'terapeuta@demo.com')->value('id')
            ?? DB::table('users')->where('email', '!=', 'jesusarizmendimaya@gmail.com')->value('id');

        $staffId   = DB::table('users')->where('email', 'admin@demo.com')->value('id') ?? $therapistId;
        $staffName = DB::table('users')->where('id', $staffId)->value('name') ?? 'Personal clínico';

        // ── Compliance (documentos legales) ──────────────────────────────────
        if (Schema::hasTable('patient_legal_acceptances')) {
            DB::table('patient_legal_acceptances')
                ->where('patient_persona_id', $personaId)
                ->delete();

            $version         = DB::table('system_settings')->where('key', 'privacy_notice_version')->value('value') ?? '2.0';
            $privacyContent  = DB::table('system_settings')->where('key', 'privacy_notice_text')->value('value') ?? '';
            $treatmentContent = DB::table('system_settings')->where('key', 'treatment_consent_text')->value('value') ?? '';

            $signedAt = now()->subDays(14)->toIso8601String();
            $docHash  = hash('sha256', $privacyContent . '|' . $version . '|privacy_notice');
            $sigHash  = hash('sha256', 'Jesús Arturo Arizméndi Maya|' . $docHash . '|' . $signedAt . '|127.0.0.1|Demo Seeder');

            // Aviso de privacidad — FIRMADO (hace 14 días desde el portal)
            DB::table('patient_legal_acceptances')->insert([
                'patient_persona_id'    => $personaId,
                'user_id'               => $userId,
                'document_type'         => 'privacy_notice',
                'version'               => $version,
                'title'                 => 'Aviso de privacidad',
                'content_snapshot'      => $privacyContent,
                'accepted_at'           => now()->subDays(14),
                'accepted_by_user_id'   => $userId,
                'accepted_by_name'      => 'Jesús Arturo Arizméndi Maya',
                'guardian_name'         => null,
                'guardian_relationship' => null,
                'status'                => 'accepted',
                'revoked_at'            => null,
                'revoked_by'            => null,
                'revocation_reason'     => null,
                'source'                => 'portal',
                'ip_address'            => '192.168.1.1',
                'user_agent'            => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Demo Seeder',
                'signer_name'           => 'Jesús Arturo Arizméndi Maya',
                'signer_role'           => 'patient',
                'signature_method'      => 'drawn',
                'signature_image_path'  => null,
                'document_hash'         => $docHash,
                'signature_hash'        => $sigHash,
                'signed_pdf_path'       => null,
                'signed_at'             => now()->subDays(14),
                'signed_ip'             => '192.168.1.1',
                'signed_user_agent'     => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Demo Seeder',
                'acceptance_metadata'   => json_encode(['seeder' => true, 'demo_user' => true]),
                'created_at'            => now()->subDays(14),
                'updated_at'            => now()->subDays(14),
            ]);

            // Consentimiento de tratamiento — PENDIENTE (no firmado aún)
            // No insertamos ningún registro → aparece como pendiente en el portal
        }

        // ── Cita futura (próxima semana) ──────────────────────────────────────
        if (Schema::hasTable('appointments') && $therapistId) {
            $apptStart = now()->startOfDay()->addDays(5)->setHour(10)->setMinute(0)->setSecond(0);

            DB::table('appointments')->updateOrInsert(
                [
                    'patient_persona_id' => $personaId,
                    'therapist_user_id'  => $therapistId,
                    'start_at'           => $apptStart,
                ],
                [
                    'end_at'     => $apptStart->copy()->addMinutes(60),
                    'status'     => 'confirmed',
                    'notes'      => 'Seguimiento rehabilitación de hombro derecho y ejercicios de core lumbar.',
                    'created_by' => $staffId,
                    'created_at' => now()->subDays(2),
                    'updated_at' => now(),
                ]
            );
        }

        // ── Solicitud de cita pendiente ───────────────────────────────────────
        if (Schema::hasTable('appointment_requests')) {
            DB::table('appointment_requests')->updateOrInsert(
                [
                    'patient_persona_id' => $personaId,
                    'preferred_date'     => now()->addDays(12)->toDateString(),
                ],
                [
                    'preferred_time'          => '09:00',
                    'reason'                  => 'Quiero continuar con las sesiones de hombro. Sigo sintiendo molestia al levantar el brazo.',
                    'notes'                   => 'Prefiero cita por la mañana entre semana.',
                    'status'                  => 'pending',
                    'rejection_reason'        => null,
                    'reviewed_by'             => null,
                    'reviewed_at'             => null,
                    'approved_appointment_id' => null,
                    'created_by'              => $userId,
                    'created_at'              => now()->subHours(6),
                    'updated_at'              => now()->subHours(6),
                ]
            );
        }

        // ── Sesión de terapia (hace 7 días) ──────────────────────────────────
        $sessionId = null;
        if (Schema::hasTable('therapy_sessions') && $therapistId) {
            $sessionId = DB::table('therapy_sessions')->updateOrInsert(
                [
                    'patient_persona_id' => $personaId,
                    'session_date'       => now()->subDays(7)->toDateString(),
                ],
                [
                    'appointment_id'    => null,
                    'therapist_user_id' => $therapistId,
                    'subjective'        => 'El paciente refiere reducción del dolor lumbar a 4/10 vs 7/10 inicial. Continúa con molestia en hombro derecho al elevar el brazo por encima de 90°.',
                    'objective'         => 'Rango articular lumbar: flexión 75°, extensión 20°. Hombro: abducción 110° (mejoría de 20°). Tensión moderada en trapecio superior bilateral.',
                    'assessment'        => 'Evolución favorable. Respuesta positiva a ejercicios de estabilización. Adherencia al programa domiciliario: buena.',
                    'plan'              => 'Progresión a ejercicios de carga axial. Incorporar trabajo excéntrico de manguito rotador. Próxima revisión en 7 días.',
                    'pain_scale'        => 4,
                    'notes'             => 'Paciente motivado y con buena comprensión del programa.',
                    'created_at'        => now()->subDays(7),
                    'updated_at'        => now()->subDays(7),
                ]
            );

            // Get the real session id
            $sessionId = DB::table('therapy_sessions')
                ->where('patient_persona_id', $personaId)
                ->where('session_date', now()->subDays(7)->toDateString())
                ->value('id');
        }

        // ── Ejercicios asignados a la sesión ─────────────────────────────────
        if ($sessionId && Schema::hasTable('session_exercises') && Schema::hasTable('exercises')) {
            $exercises = DB::table('exercises')
                ->whereIn('name', [
                    'Fortalecimiento lumbar',
                    'Movilidad de hombro',
                    'Puente de glúteos',
                ])
                ->pluck('id', 'name');

            $assignments = [
                'Fortalecimiento lumbar' => ['sets' => 3, 'reps' => 12, 'seconds' => 45, 'notes' => 'Activar abdomen antes de iniciar. Mantener espalda neutra. 4 veces por semana.'],
                'Movilidad de hombro'    => ['sets' => 3, 'reps' => 15, 'seconds' => 0,  'notes' => 'Con banda elástica ligera. Sin dolor. Detenerse si hay pinzamiento.'],
                'Puente de glúteos'      => ['sets' => 3, 'reps' => 10, 'seconds' => 30, 'notes' => 'Mantener posición 3 segundos en la cima. Respiración controlada.'],
            ];

            foreach ($assignments as $name => $cfg) {
                $exerciseId = $exercises[$name] ?? null;
                if (! $exerciseId) {
                    continue;
                }
                DB::table('session_exercises')->updateOrInsert(
                    ['session_id' => $sessionId, 'exercise_id' => $exerciseId],
                    [
                        'sets'    => $cfg['sets'],
                        'reps'    => $cfg['reps'],
                        'seconds' => $cfg['seconds'],
                        'notes'   => $cfg['notes'],
                    ]
                );
            }
        }

        // ── Archivo clínico visible al paciente ───────────────────────────────
        if (Schema::hasTable('files') && Schema::hasColumn('files', 'visible_to_patient')) {
            $demoDir = storage_path('app/private/demo');
            if (! is_dir($demoDir)) {
                mkdir($demoDir, 0755, true);
            }

            $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 612 792]>>endobj\n%%EOF";
            $fileName   = 'plan-rehabilitacion-jesus.pdf';
            $filePath   = $demoDir . '/' . $fileName;
            if (! file_exists($filePath)) {
                file_put_contents($filePath, $pdfContent);
            }

            $fileBase = [
                'disk'               => 'private',
                'file_type'          => 'document',
                'mime'               => 'application/pdf',
                'uploaded_by'        => $therapistId ?? $staffId,
                'patient_persona_id' => $personaId,
            ];

            if (Schema::hasColumn('files', 'session_id')) {
                $fileBase['session_id'] = $sessionId;
            }

            DB::table('files')->updateOrInsert(
                ['patient_persona_id' => $personaId, 'original_name' => $fileName],
                array_merge($fileBase, [
                    'path'               => 'demo/' . $fileName,
                    'size_bytes'         => 87040,
                    'visible_to_patient' => true,
                    'created_at'         => now()->subDays(7),
                    'updated_at'         => now()->subDays(7),
                ])
            );
        }
    }
}
