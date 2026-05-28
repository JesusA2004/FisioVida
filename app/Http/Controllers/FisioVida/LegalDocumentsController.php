<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Models\PatientLegalAcceptance;
use App\Services\Audit\AuditLogService;
use App\Services\Compliance\SignedDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class LegalDocumentsController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function patientOrFail(string $pacienteId): object
    {
        abort_if(! Schema::hasTable('personas'), 404);

        $patient = DB::table('personas')
            ->where('id', $pacienteId)
            ->whereNull('deleted_at')
            ->first(['id', 'nombres', 'apellido_paterno', 'fecha_nacimiento']);

        abort_if(! $patient, 404);

        return $patient;
    }

    private function currentVersion(): string
    {
        if (! Schema::hasTable('system_settings')) {
            return '1.0';
        }

        return DB::table('system_settings')
            ->where('key', 'privacy_notice_version')
            ->value('value') ?? '1.0';
    }

    private function contentSnapshot(string $docType): ?string
    {
        if (! Schema::hasTable('system_settings')) {
            return null;
        }

        $key = match ($docType) {
            'privacy_notice'    => 'privacy_notice_text',
            'sensitive_data'    => 'sensitive_data_consent_text',
            'treatment_consent' => 'treatment_consent_text',
            'image_consent'     => 'image_consent_text',
            'minor_consent'     => 'minor_consent_text',
            default             => null,
        };

        return $key ? DB::table('system_settings')->where('key', $key)->value('value') : null;
    }

    private function clinicSettings(): array
    {
        if (! Schema::hasTable('system_settings')) {
            return [];
        }

        return DB::table('system_settings')
            ->whereIn('key', ['clinic_name', 'legal_business_name', 'privacy_address'])
            ->pluck('value', 'key')
            ->toArray();
    }

    private function patientFullName(object $patient): string
    {
        return trim(($patient->nombres ?? '') . ' ' . ($patient->apellido_paterno ?? ''));
    }

    // ── POST /pacientes/{paciente}/documentos-legales ─────────────────────────
    public function store(Request $request, string $paciente)
    {
        abort_if(! Schema::hasTable('patient_legal_acceptances'), 503);

        $patient = $this->patientOrFail($paciente);

        $data = $request->validate([
            'document_type'         => ['required', Rule::in(array_keys(PatientLegalAcceptance::$documentTypes))],
            'version'               => ['nullable', 'string', 'max:20'],
            'guardian_name'         => ['nullable', 'string', 'max:200'],
            'guardian_relationship' => ['nullable', 'string', 'max:80'],
            'signer_name'           => ['required', 'string', 'max:200'],
            // base64 PNG — max ~600 KB in base64 chars (~450 KB binary)
            'signature_image'       => ['nullable', 'string', 'max:614400'],
        ], [
            'document_type.required' => 'Selecciona el tipo de documento.',
            'document_type.in'       => 'Tipo de documento no válido.',
            'signer_name.required'   => 'Ingresa el nombre del firmante.',
        ]);

        $docType  = $data['document_type'];
        $version  = $data['version'] ?? $this->currentVersion();
        $label    = PatientLegalAcceptance::$documentTypes[$docType];
        $patientName = $this->patientFullName($patient);

        $sds              = app(SignedDocumentService::class);
        $contentSnapshot  = $this->contentSnapshot($docType);
        $signedAt         = now();
        $signerName       = $data['signer_name'];
        $signatureMethod  = 'staff_recorded';
        $sigImagePath     = null;
        $documentHash     = null;
        $signatureHash    = null;
        $signedPdfPath    = null;

        if ($contentSnapshot) {
            $documentHash = $sds->documentHash($contentSnapshot, $version, $docType);
        }

        if (! empty($data['signature_image'])) {
            try {
                $sigImagePath    = $sds->saveSignatureImage($data['signature_image'], (int) $paciente);
                $signatureMethod = 'drawn_signature';

                if ($documentHash) {
                    $signatureHash = $sds->signatureHash(
                        $signerName,
                        $documentHash,
                        $signedAt->toIso8601String(),
                        $request->ip(),
                        (string) $request->userAgent()
                    );
                }
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['signature_image' => $e->getMessage()]);
            }
        }

        // Build PDF
        $acceptance = [
            'title'              => $label,
            'document_type'      => $docType,
            'version'            => $version,
            'content_snapshot'   => $contentSnapshot,
            'signer_name'        => $signerName,
            'signer_role'        => 'staff',
            'guardian_name'      => $data['guardian_name'] ?? null,
            'guardian_relationship' => $data['guardian_relationship'] ?? null,
            'source'             => 'staff',
            'document_hash'      => $documentHash,
            'signature_hash'     => $signatureHash,
            'signature_image_path' => $sigImagePath,
            'accepted_at'        => $signedAt->toDateTimeString(),
            'signed_at'          => $signedAt->toDateTimeString(),
            'signed_ip'          => $request->ip(),
            'ip_address'         => $request->ip(),
            'signed_user_agent'  => substr((string) $request->userAgent(), 0, 500),
            'user_agent'         => substr((string) $request->userAgent(), 0, 500),
        ];

        $signedPdfPath = $sds->generatePdf(
            $sds->buildPdfData($acceptance, ['full_name' => $patientName], $this->clinicSettings()),
            (int) $paciente
        );

        DB::table('patient_legal_acceptances')->insert([
            'patient_persona_id'    => (int) $paciente,
            'user_id'               => $request->user()?->id,
            'document_type'         => $docType,
            'version'               => $version,
            'title'                 => $label,
            'content_snapshot'      => $contentSnapshot,
            'accepted_at'           => $signedAt,
            'accepted_by_user_id'   => $request->user()?->id,
            'accepted_by_name'      => $request->user()?->name,
            'guardian_name'         => $data['guardian_name'] ?? null,
            'guardian_relationship' => $data['guardian_relationship'] ?? null,
            'ip_address'            => $request->ip(),
            'user_agent'            => substr((string) $request->userAgent(), 0, 500),
            'status'                => 'accepted',
            'source'                => 'staff',
            'signer_name'           => $signerName,
            'signer_role'           => 'staff',
            'signature_method'      => $signatureMethod,
            'signature_image_path'  => $sigImagePath,
            'document_hash'         => $documentHash,
            'signature_hash'        => $signatureHash,
            'signed_pdf_path'       => $signedPdfPath,
            'signed_at'             => $signedAt,
            'signed_ip'             => $request->ip(),
            'signed_user_agent'     => substr((string) $request->userAgent(), 0, 500),
            'acceptance_metadata'   => json_encode(['registered_by' => $request->user()?->name]),
            'created_at'            => $signedAt,
            'updated_at'            => $signedAt,
        ]);

        app(AuditLogService::class)->record([
            'user_id'        => $request->user()?->id,
            'action'         => 'legal_document_signed',
            'module'         => 'Cumplimiento',
            'auditable_type' => 'persona',
            'auditable_id'   => (int) $paciente,
            'human_message'  => 'El usuario ' . ($request->user()?->name ?? 'Desconocido')
                . ' registró firma de "' . $label . '" v' . $version
                . ' para "' . $patientName . '" (método: ' . $signatureMethod . ').',
            'new_values'     => [
                'document_type'    => $docType,
                'version'          => $version,
                'signature_method' => $signatureMethod,
                'signed_at'        => $signedAt->toIso8601String(),
            ],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return back()->with('success', '"' . $label . '" firmado y registrado correctamente.');
    }

    // ── PATCH /pacientes/{paciente}/documentos-legales/{aceptacion}/revocar ──
    public function revoke(Request $request, string $paciente, int $aceptacion)
    {
        abort_if(! Schema::hasTable('patient_legal_acceptances'), 503);

        $patient = $this->patientOrFail($paciente);

        $acceptance = DB::table('patient_legal_acceptances')
            ->where('id', $aceptacion)
            ->where('patient_persona_id', $paciente)
            ->where('status', 'accepted')
            ->first();

        abort_if(! $acceptance, 404);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::table('patient_legal_acceptances')
            ->where('id', $aceptacion)
            ->update([
                'status'            => 'revoked',
                'revoked_at'        => now(),
                'revoked_by'        => $request->user()?->id,
                'revocation_reason' => $data['reason'] ?? null,
                'updated_at'        => now(),
            ]);

        $patientName = $this->patientFullName($patient);

        app(AuditLogService::class)->record([
            'user_id'        => $request->user()?->id,
            'action'         => 'legal_document_revoked',
            'module'         => 'Cumplimiento',
            'auditable_type' => 'persona',
            'auditable_id'   => (int) $paciente,
            'human_message'  => 'El usuario ' . ($request->user()?->name ?? 'Desconocido')
                . ' revocó "' . ($acceptance->title ?? $acceptance->document_type)
                . '" para "' . $patientName . '".',
            'new_values'     => ['status' => 'revoked', 'reason' => $data['reason'] ?? null],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return back()->with('success', 'Consentimiento revocado correctamente.');
    }

    // ── POST /mi-portal/documentos/aceptar ───────────────────────────────────
    public function portalAccept(Request $request)
    {
        $user      = $request->user();
        $personaId = $user->persona_id ?? null;
        abort_if(! $personaId, 403, 'Tu cuenta no está vinculada a un expediente.');
        abort_if(! Schema::hasTable('patient_legal_acceptances'), 503);

        $allowed = ! Schema::hasTable('system_settings') || DB::table('system_settings')
            ->where('key', 'allow_patient_portal_acceptance')
            ->value('value') !== '0';

        abort_if(! $allowed, 403, 'La aceptación desde el portal no está habilitada.');

        $data = $request->validate([
            'document_type'         => ['required', Rule::in(array_keys(PatientLegalAcceptance::$documentTypes))],
            'version'               => ['nullable', 'string', 'max:20'],
            'confirmed'             => ['required', 'boolean', 'accepted'],
            'signer_name'           => ['required', 'string', 'max:200'],
            'signature_image'       => ['required', 'string', 'max:614400'],
            'guardian_name'         => ['nullable', 'string', 'max:200'],
            'guardian_relationship' => ['nullable', 'string', 'max:80'],
        ], [
            'document_type.required' => 'Selecciona el documento a aceptar.',
            'confirmed.required'     => 'Debes confirmar que has leído el documento.',
            'confirmed.accepted'     => 'Debes marcar la casilla de aceptación.',
            'signer_name.required'   => 'Ingresa tu nombre completo.',
            'signature_image.required' => 'Se requiere firma dibujada.',
        ]);

        $patient = DB::table('personas')
            ->where('id', $personaId)
            ->whereNull('deleted_at')
            ->first(['id', 'nombres', 'apellido_paterno']);

        abort_if(! $patient, 404);

        $docType     = $data['document_type'];
        $version     = $data['version'] ?? $this->currentVersion();
        $label       = PatientLegalAcceptance::$documentTypes[$docType];
        $patientName = $this->patientFullName($patient);

        // Prevent duplicate active acceptance for same version
        $existing = DB::table('patient_legal_acceptances')
            ->where('patient_persona_id', $personaId)
            ->where('document_type', $docType)
            ->where('version', $version)
            ->where('status', 'accepted')
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'Este documento ya ha sido aceptado en esta versión.',
            ], 200);
        }

        $sds             = app(SignedDocumentService::class);
        $contentSnapshot = $this->contentSnapshot($docType);
        $signedAt        = now();
        $signerName      = $data['signer_name'];
        $signerRole      = empty($data['guardian_name']) ? 'patient' : 'guardian';

        $sigImagePath  = null;
        $documentHash  = null;
        $signatureHash = null;

        if ($contentSnapshot) {
            $documentHash = $sds->documentHash($contentSnapshot, $version, $docType);
        }

        try {
            $sigImagePath  = $sds->saveSignatureImage($data['signature_image'], (int) $personaId);

            if ($documentHash) {
                $signatureHash = $sds->signatureHash(
                    $signerName,
                    $documentHash,
                    $signedAt->toIso8601String(),
                    $request->ip(),
                    (string) $request->userAgent()
                );
            }
        } catch (\InvalidArgumentException $e) {
            return response()->json(['errors' => ['signature_image' => [$e->getMessage()]]], 422);
        }

        // PDF
        $acceptance = [
            'title'              => $label,
            'document_type'      => $docType,
            'version'            => $version,
            'content_snapshot'   => $contentSnapshot,
            'signer_name'        => $signerName,
            'signer_role'        => $signerRole,
            'guardian_name'      => $data['guardian_name'] ?? null,
            'guardian_relationship' => $data['guardian_relationship'] ?? null,
            'source'             => 'portal',
            'document_hash'      => $documentHash,
            'signature_hash'     => $signatureHash,
            'signature_image_path' => $sigImagePath,
            'accepted_at'        => $signedAt->toDateTimeString(),
            'signed_at'          => $signedAt->toDateTimeString(),
            'signed_ip'          => $request->ip(),
            'ip_address'         => $request->ip(),
            'signed_user_agent'  => substr((string) $request->userAgent(), 0, 500),
            'user_agent'         => substr((string) $request->userAgent(), 0, 500),
        ];

        $signedPdfPath = $sds->generatePdf(
            $sds->buildPdfData($acceptance, ['full_name' => $patientName], $this->clinicSettings()),
            (int) $personaId
        );

        DB::table('patient_legal_acceptances')->insert([
            'patient_persona_id'    => (int) $personaId,
            'user_id'               => $user->id,
            'document_type'         => $docType,
            'version'               => $version,
            'title'                 => $label,
            'content_snapshot'      => $contentSnapshot,
            'accepted_at'           => $signedAt,
            'accepted_by_user_id'   => null,
            'accepted_by_name'      => $patientName,
            'guardian_name'         => $data['guardian_name'] ?? null,
            'guardian_relationship' => $data['guardian_relationship'] ?? null,
            'ip_address'            => $request->ip(),
            'user_agent'            => substr((string) $request->userAgent(), 0, 500),
            'status'                => 'accepted',
            'source'                => 'portal',
            'signer_name'           => $signerName,
            'signer_role'           => $signerRole,
            'signature_method'      => 'drawn_signature',
            'signature_image_path'  => $sigImagePath,
            'document_hash'         => $documentHash,
            'signature_hash'        => $signatureHash,
            'signed_pdf_path'       => $signedPdfPath,
            'signed_at'             => $signedAt,
            'signed_ip'             => $request->ip(),
            'signed_user_agent'     => substr((string) $request->userAgent(), 0, 500),
            'acceptance_metadata'   => json_encode([
                'confirmed_by_checkbox' => true,
                'portal_acceptance'     => true,
            ]),
            'created_at'            => $signedAt,
            'updated_at'            => $signedAt,
        ]);

        app(AuditLogService::class)->record([
            'user_id'        => $user->id,
            'action'         => 'legal_document_signed_portal',
            'module'         => 'Cumplimiento',
            'auditable_type' => 'persona',
            'auditable_id'   => (int) $personaId,
            'human_message'  => 'El paciente "' . $patientName . '" firmó "' . $label . '" v' . $version . ' desde el portal con firma dibujada.',
            'new_values'     => [
                'document_type'    => $docType,
                'version'          => $version,
                'signature_method' => 'drawn_signature',
                'signer_role'      => $signerRole,
            ],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return response()->json([
            'message'  => '"' . $label . '" firmado y registrado correctamente.',
            'has_pdf'  => $signedPdfPath !== null,
        ]);
    }

    // ── GET /mi-portal/documentos/{aceptacion}/descargar ─────────────────────
    public function downloadPortal(Request $request, int $aceptacion)
    {
        $user      = $request->user();
        $personaId = $user->persona_id ?? null;
        abort_if(! $personaId, 403);

        $acceptance = DB::table('patient_legal_acceptances')
            ->where('id', $aceptacion)
            ->where('patient_persona_id', $personaId) // patient can only access their own
            ->first();

        abort_if(! $acceptance, 404);

        app(AuditLogService::class)->record([
            'user_id'        => $user->id,
            'action'         => 'legal_document_downloaded',
            'module'         => 'Cumplimiento',
            'auditable_type' => 'persona',
            'auditable_id'   => (int) $personaId,
            'human_message'  => 'Paciente descargó PDF de "' . ($acceptance->title ?? $acceptance->document_type) . '".',
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        if (empty($acceptance->signed_pdf_path)) {
            abort(404, 'El PDF firmado no está disponible para este documento.');
        }

        $filename = 'documento-' . $acceptance->document_type . '-v' . $acceptance->version . '.pdf';

        return app(SignedDocumentService::class)->download($acceptance->signed_pdf_path, $filename);
    }

    // ── GET /pacientes/{paciente}/documentos-legales/{aceptacion}/descargar ───
    public function downloadStaff(Request $request, string $paciente, int $aceptacion)
    {
        $this->patientOrFail($paciente);

        $acceptance = DB::table('patient_legal_acceptances')
            ->where('id', $aceptacion)
            ->where('patient_persona_id', $paciente)
            ->first();

        abort_if(! $acceptance, 404);

        app(AuditLogService::class)->record([
            'user_id'        => $request->user()?->id,
            'action'         => 'legal_document_downloaded_staff',
            'module'         => 'Cumplimiento',
            'auditable_type' => 'persona',
            'auditable_id'   => (int) $paciente,
            'human_message'  => ($request->user()?->name ?? 'Staff') . ' descargó PDF de "'
                . ($acceptance->title ?? $acceptance->document_type) . '" del paciente #' . $paciente . '.',
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        if (empty($acceptance->signed_pdf_path)) {
            abort(404, 'El PDF firmado no está disponible para este documento.');
        }

        $filename = 'paciente-' . $paciente . '-' . $acceptance->document_type . '-v' . $acceptance->version . '.pdf';

        return app(SignedDocumentService::class)->download($acceptance->signed_pdf_path, $filename);
    }
}
