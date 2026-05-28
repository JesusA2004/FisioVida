<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientLegalAcceptance extends Model
{
    protected $table = 'patient_legal_acceptances';

    protected $fillable = [
        'patient_persona_id',
        'user_id',
        'document_type',
        'version',
        'title',
        'content_snapshot',
        'accepted_at',
        'accepted_by_user_id',
        'accepted_by_name',
        'guardian_name',
        'guardian_relationship',
        'ip_address',
        'user_agent',
        'source',
        'status',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        // Firma digital simple
        'signer_name',
        'signer_role',
        'signature_method',
        'signature_image_path',
        'signature_hash',
        'document_hash',
        'signed_pdf_path',
        'signed_at',
        'signed_ip',
        'signed_user_agent',
        'acceptance_metadata',
    ];

    protected $casts = [
        'accepted_at'        => 'datetime',
        'revoked_at'         => 'datetime',
        'signed_at'          => 'datetime',
        'acceptance_metadata'=> 'array',
    ];

    public static array $documentTypes = [
        'privacy_notice'    => 'Aviso de privacidad',
        'sensitive_data'    => 'Consentimiento de datos sensibles de salud',
        'treatment_consent' => 'Consentimiento de tratamiento',
        'image_consent'     => 'Consentimiento de uso de imágenes/evidencia',
        'minor_consent'     => 'Consentimiento para menor de edad',
    ];
}
