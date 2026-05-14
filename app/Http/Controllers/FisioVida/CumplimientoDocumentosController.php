<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CumplimientoDocumentosController extends Controller
{
    private function getPatient(string $pacienteId): object
    {
        abort_if(! Schema::hasTable('personas'), 404);

        $patient = DB::table('personas')
            ->where('id', $pacienteId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(! $patient, 404);

        return $patient;
    }

    private function audit(Request $request, string $action, int $patientId, string $patientName, string $docType): void
    {
        app(AuditLogService::class)->record([
            'user_id'        => $request->user()?->id,
            'action'         => 'compliance_document_printed',
            'module'         => 'Cumplimiento',
            'auditable_type' => 'persona',
            'auditable_id'   => $patientId,
            'human_message'  => 'El usuario ' . ($request->user()?->name ?? 'Desconocido') . ' imprimió el documento "' . $docType . '" para el paciente "' . $patientName . '".',
            'new_values'     => ['document_type' => $action, 'patient_id' => $patientId],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);
    }

    private function clinicData(): array
    {
        $settings = Schema::hasTable('system_settings')
            ? DB::table('system_settings')->pluck('value', 'key')->all()
            : [];

        return [
            'clinic_name'  => $settings['clinic_name'] ?? 'FisioVida',
            'clinic_phone' => $settings['clinic_phone'] ?? '',
            'clinic_email' => $settings['clinic_email'] ?? '',
            'clinic_address' => $settings['clinic_address'] ?? '',
        ];
    }

    public function printIntakeForm(Request $request, string $paciente)
    {
        $patient = $this->getPatient($paciente);
        $patientName = trim(($patient->nombres ?? '') . ' ' . ($patient->apellido_paterno ?? '') . ' ' . ($patient->apellido_materno ?? ''));
        $clinic = $this->clinicData();

        $this->audit($request, 'intake_form_printed', (int) $paciente, $patientName, 'Ficha de ingreso');

        return view('compliance.intake-form', compact('patient', 'patientName', 'clinic'));
    }

    public function printPrivacyNotice(Request $request, string $paciente)
    {
        $patient = $this->getPatient($paciente);
        $patientName = trim(($patient->nombres ?? '') . ' ' . ($patient->apellido_paterno ?? '') . ' ' . ($patient->apellido_materno ?? ''));
        $clinic = $this->clinicData();

        $this->audit($request, 'privacy_notice_printed', (int) $paciente, $patientName, 'Aviso de privacidad');

        return view('compliance.privacy-notice', compact('patient', 'patientName', 'clinic'));
    }

    public function printTreatmentConsent(Request $request, string $paciente)
    {
        $patient = $this->getPatient($paciente);
        $patientName = trim(($patient->nombres ?? '') . ' ' . ($patient->apellido_paterno ?? '') . ' ' . ($patient->apellido_materno ?? ''));
        $clinic = $this->clinicData();

        $this->audit($request, 'treatment_consent_printed', (int) $paciente, $patientName, 'Consentimiento de tratamiento');

        return view('compliance.treatment-consent', compact('patient', 'patientName', 'clinic'));
    }

    public function printImageConsent(Request $request, string $paciente)
    {
        $patient = $this->getPatient($paciente);
        $patientName = trim(($patient->nombres ?? '') . ' ' . ($patient->apellido_paterno ?? '') . ' ' . ($patient->apellido_materno ?? ''));
        $clinic = $this->clinicData();

        $this->audit($request, 'image_consent_printed', (int) $paciente, $patientName, 'Consentimiento de imágenes');

        return view('compliance.image-consent', compact('patient', 'patientName', 'clinic'));
    }

    public function printSensitiveDataConsent(Request $request, string $paciente)
    {
        $patient = $this->getPatient($paciente);
        $patientName = trim(($patient->nombres ?? '') . ' ' . ($patient->apellido_paterno ?? '') . ' ' . ($patient->apellido_materno ?? ''));
        $clinic = $this->clinicData();

        $this->audit($request, 'sensitive_data_consent_printed', (int) $paciente, $patientName, 'Consentimiento de datos sensibles');

        return view('compliance.sensitive-data-consent', compact('patient', 'patientName', 'clinic'));
    }
}
