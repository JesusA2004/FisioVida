<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CumplimientoDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('patient_legal_acceptances')) {
            return;
        }
        if (! Schema::hasTable('personas')) {
            return;
        }

        $sofia   = DB::table('personas')->where('nombres', 'like', 'Sofía%')->whereNull('deleted_at')->first(['id', 'nombres']);
        $daniel  = DB::table('personas')->where('nombres', 'like', 'Daniel%')->whereNull('deleted_at')->first(['id', 'nombres']);
        $natalia = DB::table('personas')->where('nombres', 'like', 'Natalia%')->whereNull('deleted_at')->first(['id', 'nombres']);

        $adminUser = DB::table('users')->where('name', 'like', '%Admin%')->first(['id', 'name']);
        $staffName = $adminUser?->name ?? 'Personal clínico';
        $staffId   = $adminUser?->id ?? null;

        $version     = DB::table('system_settings')->where('key', 'privacy_notice_version')->value('value') ?? '1.0';
        $getSnapshot = fn (string $key) => DB::table('system_settings')->where('key', $key)->value('value');

        $makeHash = function (string $content, string $ver, string $docType, string $signer, string $signedAt): array {
            $docHash = hash('sha256', $content . '|' . $ver . '|' . $docType);
            $sigHash = hash('sha256', $signer . '|' . $docHash . '|' . $signedAt . '|127.0.0.1|Demo Seeder');
            return ['document_hash' => $docHash, 'signature_hash' => $sigHash];
        };

        // ── Sofía: aviso de privacidad, tratamiento y datos sensibles firmados ─
        if ($sofia) {
            DB::table('patient_legal_acceptances')
                ->where('patient_persona_id', $sofia->id)
                ->delete();

            $sofiaSignedAt = now()->subDays(30)->toIso8601String();

            $base = [
                'patient_persona_id'    => $sofia->id,
                'user_id'               => $staffId,
                'document_type'         => '',
                'version'               => $version,
                'title'                 => '',
                'content_snapshot'      => null,
                'accepted_at'           => now()->subDays(30),
                'accepted_by_user_id'   => $staffId,
                'accepted_by_name'      => $staffName,
                'guardian_name'         => null,
                'guardian_relationship' => null,
                'status'                => 'accepted',
                'revoked_at'            => null,
                'revoked_by'            => null,
                'revocation_reason'     => null,
                'source'                => 'staff',
                'ip_address'            => '127.0.0.1',
                'user_agent'            => 'Demo Seeder',
                // Firma digital simple
                'signer_name'           => 'Sofía Ramírez',
                'signer_role'           => 'patient',
                'signature_method'      => 'staff_recorded',
                'signature_image_path'  => null,
                'document_hash'         => null,
                'signature_hash'        => null,
                'signed_pdf_path'       => null,
                'signed_at'             => now()->subDays(30),
                'signed_ip'             => '127.0.0.1',
                'signed_user_agent'     => 'Demo Seeder',
                'acceptance_metadata'   => json_encode(['seeder' => true]),
                'created_at'            => now()->subDays(30),
                'updated_at'            => now()->subDays(30),
            ];

            $privacyContent  = $getSnapshot('privacy_notice_text') ?? '';
            $treatmentContent = $getSnapshot('treatment_consent_text') ?? '';
            $sensitiveContent = $getSnapshot('sensitive_data_consent_text') ?? '';

            [$ph1, $sh1] = array_values($makeHash($privacyContent, $version, 'privacy_notice', 'Sofía Ramírez', $sofiaSignedAt));
            [$ph2, $sh2] = array_values($makeHash($treatmentContent, $version, 'treatment_consent', 'Sofía Ramírez', $sofiaSignedAt));
            [$ph3, $sh3] = array_values($makeHash($sensitiveContent, $version, 'sensitive_data', 'Sofía Ramírez', $sofiaSignedAt));

            DB::table('patient_legal_acceptances')->insert([
                array_merge($base, [
                    'document_type'    => 'privacy_notice',
                    'title'            => 'Aviso de privacidad',
                    'content_snapshot' => $privacyContent,
                    'document_hash'    => $ph1,
                    'signature_hash'   => $sh1,
                ]),
                array_merge($base, [
                    'document_type'    => 'treatment_consent',
                    'title'            => 'Consentimiento de tratamiento',
                    'content_snapshot' => $treatmentContent,
                    'document_hash'    => $ph2,
                    'signature_hash'   => $sh2,
                ]),
                array_merge($base, [
                    'document_type'    => 'sensitive_data',
                    'title'            => 'Consentimiento de datos sensibles de salud',
                    'content_snapshot' => $sensitiveContent,
                    'document_hash'    => $ph3,
                    'signature_hash'   => $sh3,
                ]),
                // image_consent: pendiente (ausente intencionalmente para demo)
            ]);
        }

        // ── Daniel: todos los documentos pendientes ──────────────────────────
        if ($daniel) {
            DB::table('patient_legal_acceptances')
                ->where('patient_persona_id', $daniel->id)
                ->delete();
            // Sin registros — todos pendientes
        }

        // ── Natalia: aviso aceptado, tratamiento revocado ─────────────────────
        if ($natalia) {
            DB::table('patient_legal_acceptances')
                ->where('patient_persona_id', $natalia->id)
                ->delete();

            $nataliaSignedAt = now()->subDays(60)->toIso8601String();
            $nataliaPrivacy  = $getSnapshot('privacy_notice_text') ?? '';
            $nataliaTreatment = $getSnapshot('treatment_consent_text') ?? '';

            [$nph, $nsh] = array_values($makeHash($nataliaPrivacy, $version, 'privacy_notice', 'Natalia López', $nataliaSignedAt));

            $baseRow = [
                'patient_persona_id'    => $natalia->id,
                'user_id'               => $staffId,
                'document_type'         => '',
                'version'               => $version,
                'title'                 => '',
                'content_snapshot'      => null,
                'accepted_at'           => now()->subDays(60),
                'accepted_by_user_id'   => $staffId,
                'accepted_by_name'      => $staffName,
                'guardian_name'         => null,
                'guardian_relationship' => null,
                'status'                => 'accepted',
                'revoked_at'            => null,
                'revoked_by'            => null,
                'revocation_reason'     => null,
                'source'                => 'staff',
                'ip_address'            => '127.0.0.1',
                'user_agent'            => 'Demo Seeder',
                'signer_name'           => 'Natalia López',
                'signer_role'           => 'patient',
                'signature_method'      => 'staff_recorded',
                'signature_image_path'  => null,
                'document_hash'         => null,
                'signature_hash'        => null,
                'signed_pdf_path'       => null,
                'signed_at'             => now()->subDays(60),
                'signed_ip'             => '127.0.0.1',
                'signed_user_agent'     => 'Demo Seeder',
                'acceptance_metadata'   => json_encode(['seeder' => true]),
                'created_at'            => now()->subDays(60),
                'updated_at'            => now()->subDays(60),
            ];

            DB::table('patient_legal_acceptances')->insert([
                array_merge($baseRow, [
                    'document_type'    => 'privacy_notice',
                    'title'            => 'Aviso de privacidad',
                    'content_snapshot' => $nataliaPrivacy,
                    'document_hash'    => $nph,
                    'signature_hash'   => $nsh,
                ]),
                array_merge($baseRow, [
                    'document_type'         => 'treatment_consent',
                    'title'                 => 'Consentimiento de tratamiento',
                    'content_snapshot'      => $nataliaTreatment,
                    'document_hash'         => null,
                    'signature_hash'        => null,
                    'status'                => 'revoked',
                    'revoked_at'            => now()->subDays(10),
                    'revoked_by'            => $staffId,
                    'revocation_reason'     => 'El paciente solicitó revocar el consentimiento (ejemplo demo).',
                    'updated_at'            => now()->subDays(10),
                ]),
            ]);
        }
    }
}
