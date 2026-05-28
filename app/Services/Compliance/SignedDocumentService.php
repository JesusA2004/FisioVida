<?php

namespace App\Services\Compliance;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SignedDocumentService
{
    /**
     * Save base64 PNG signature to private storage and return path.
     */
    public function saveSignatureImage(string $base64, int $patientId): string
    {
        $data = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $binary = base64_decode($data, strict: true);

        if ($binary === false || strlen($binary) === 0) {
            throw new \InvalidArgumentException('Imagen de firma no válida.');
        }

        // Validate PNG magic bytes
        if (substr($binary, 0, 4) !== "\x89PNG") {
            throw new \InvalidArgumentException('Solo se aceptan imágenes PNG para la firma.');
        }

        $path = "signatures/{$patientId}/" . Str::uuid() . '.png';
        Storage::disk('private')->put($path, $binary);

        return $path;
    }

    /**
     * SHA-256 hash of document content + version + type.
     */
    public function documentHash(string $content, string $version, string $docType): string
    {
        return hash('sha256', $content . '|' . $version . '|' . $docType);
    }

    /**
     * SHA-256 hash of signature evidence fields.
     */
    public function signatureHash(
        string $signerName,
        string $documentHash,
        string $signedAt,
        string $signedIp,
        string $userAgent
    ): string {
        return hash('sha256', $signerName . '|' . $documentHash . '|' . $signedAt . '|' . $signedIp . '|' . $userAgent);
    }

    /**
     * Generate signed PDF and save to private storage. Returns path.
     * Does NOT throw — returns null on failure so acceptance record is still saved.
     */
    public function generatePdf(array $data, int $patientId): ?string
    {
        try {
            $pdf = Pdf::loadView('pdf.signed-document', $data);
            $pdf->setPaper('letter', 'portrait');

            $path = "legal-documents/{$patientId}/" . Str::uuid() . '.pdf';
            Storage::disk('private')->put($path, $pdf->output());

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Read signature image from private disk as base64 PNG for PDF embedding.
     */
    public function signatureBase64(string $path): string
    {
        if (!Storage::disk('private')->exists($path)) {
            return '';
        }

        return base64_encode(Storage::disk('private')->get($path));
    }

    /**
     * Stream a private file as a download (attachment) response.
     */
    public function download(string $path, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_if(!Storage::disk('private')->exists($path), 404, 'El archivo no está disponible.');

        return Storage::disk('private')->download($path, $filename);
    }

    /**
     * Stream a private file inline (opens in browser, not downloaded).
     */
    public function stream(string $path, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_if(!Storage::disk('private')->exists($path), 404, 'El archivo no está disponible.');

        return Storage::disk('private')->response($path, $filename, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Replace {variable} tokens in a template string with clinic settings values.
     * Also strips any remaining [placeholder] markers that were never replaced.
     */
    public function resolveTemplate(string $text, array $vars): string
    {
        // Replace {var} tokens
        foreach ($vars as $key => $value) {
            $text = str_replace('{' . $key . '}', (string) ($value ?? ''), $text);
        }

        // Strip any remaining [bracketed placeholders] — never show them
        $text = preg_replace('/\[[^\]]{1,80}\]/', '', $text);

        return $text;
    }

    /**
     * Prepare PDF data array from an acceptance record and supporting info.
     */
    public function buildPdfData(array $acceptance, array $patient, array $clinicSettings): array
    {
        $signatureBase64 = '';
        if (!empty($acceptance['signature_image_path'])) {
            $signatureBase64 = $this->signatureBase64($acceptance['signature_image_path']);
        }

        $signerRoleLabel = match ($acceptance['signer_role'] ?? 'staff') {
            'patient'  => 'Paciente',
            'guardian' => 'Tutor / Representante legal',
            'staff'    => 'Personal clínico',
            default    => $acceptance['signer_role'] ?? 'Personal clínico',
        };

        $sourceLabel = ($acceptance['source'] ?? 'staff') === 'portal'
            ? 'Portal del paciente (firma en línea)'
            : 'Atención presencial (staff)';

        $contentTruncated = $acceptance['content_snapshot'] ?? '';
        if (strlen($contentTruncated) > 4000) {
            $contentTruncated = substr($contentTruncated, 0, 4000) . "\n\n[Texto completo registrado en el sistema]";
        }

        return [
            'clinicName'           => $clinicSettings['clinic_name'] ?? 'Clínica',
            'clinicLegal'          => $clinicSettings['legal_business_name'] ?? null,
            'clinicAddress'        => $clinicSettings['privacy_address'] ?? null,
            'patientName'          => $patient['full_name'],
            'signerName'           => $acceptance['signer_name'] ?? ($patient['full_name']),
            'signerRole'           => $acceptance['signer_role'] ?? 'staff',
            'signerRoleLabel'      => $signerRoleLabel,
            'guardianName'         => $acceptance['guardian_name'] ?? null,
            'guardianRelationship' => $acceptance['guardian_relationship'] ?? null,
            'docLabel'             => $acceptance['title'] ?? $acceptance['document_type'],
            'version'              => $acceptance['version'] ?? '1.0',
            'signedAt'             => (function () use ($acceptance): string {
                                            $tz  = new \DateTimeZone('America/Mexico_City');
                                            $raw = $acceptance['signed_at'] ?? $acceptance['accepted_at'] ?? null;
                                            if (! $raw) {
                                                return '—';
                                            }
                                            return (new \DateTime($raw, new \DateTimeZone('UTC')))
                                                ->setTimezone($tz)
                                                ->format('d/m/Y H:i:s');
                                        })(),
            'signedIp'             => $acceptance['signed_ip'] ?? $acceptance['ip_address'] ?? '—',
            'userAgent'            => substr($acceptance['signed_user_agent'] ?? $acceptance['user_agent'] ?? '', 0, 100),
            'source'               => $acceptance['source'] ?? 'staff',
            'sourceLabel'          => $sourceLabel,
            'documentHash'         => $acceptance['document_hash'] ?? '—',
            'signatureHash'        => $acceptance['signature_hash'] ?? '—',
            'signatureImageBase64' => $signatureBase64,
            'contentSnapshot'      => $contentTruncated,
            'generatedAt'          => now('America/Mexico_City')->format('d/m/Y H:i:s'),
        ];
    }
}
