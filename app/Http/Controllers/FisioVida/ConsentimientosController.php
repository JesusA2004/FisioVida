<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ConsentimientosController extends Controller
{
    // Tipos de consentimiento clínico (privacidad se gestiona por separado en privacy_notice_acceptances)
    private const TYPES = ['tratamiento', 'imagenes', 'datos_sensibles'];

    public function store(Request $request, string $paciente)
    {
        abort_if(! Schema::hasTable('personas'), 404);

        $patient = DB::table('personas')
            ->where('id', $paciente)
            ->whereNull('deleted_at')
            ->first(['id', 'nombres', 'apellido_paterno']);

        abort_if(! $patient, 404);

        $data = $request->validate([
            'consent_type' => ['required', Rule::in(self::TYPES)],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ], [
            'consent_type.required' => 'Selecciona el tipo de consentimiento.',
            'consent_type.in'       => 'Tipo de consentimiento no válido.',
        ]);

        DB::table('patient_consents')->insert([
            'patient_persona_id'  => (int) $paciente,
            'consent_type'        => $data['consent_type'],
            'accepted_at'         => now(),
            'accepted_by_user_id' => $request->user()?->id,
            'notes'               => $data['notes'] ?? null,
            'ip_address'          => $request->ip(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $patientName = trim(($patient->nombres ?? '') . ' ' . ($patient->apellido_paterno ?? ''));

        app(AuditLogService::class)->record([
            'user_id'        => $request->user()?->id,
            'action'         => 'consent_registered',
            'module'         => 'Pacientes',
            'auditable_type' => 'persona',
            'auditable_id'   => (int) $paciente,
            'human_message'  => 'El usuario ' . ($request->user()?->name ?? 'Desconocido') . ' registró consentimiento "' . $data['consent_type'] . '" para "' . $patientName . '".',
            'new_values'     => ['consent_type' => $data['consent_type'], 'accepted_at' => now()->toIso8601String()],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return back()->with('success', 'Consentimiento registrado correctamente.');
    }

    public function storePrivacy(Request $request, string $paciente)
    {
        abort_if(! Schema::hasTable('personas'), 404);
        abort_if(! Schema::hasTable('privacy_notice_acceptances'), 503);

        $patient = DB::table('personas')
            ->where('id', $paciente)
            ->whereNull('deleted_at')
            ->first(['id', 'nombres', 'apellido_paterno']);

        abort_if(! $patient, 404);

        $data = $request->validate([
            'version' => ['nullable', 'string', 'max:20'],
            'notes'   => ['nullable', 'string', 'max:500'],
        ]);

        $version = $data['version'] ?? '1.0';

        DB::table('privacy_notice_acceptances')->insert([
            'patient_persona_id' => (int) $paciente,
            'version'            => $version,
            'accepted_at'        => now(),
            'accepted_by'        => $request->user()?->id,
            'ip'                 => $request->ip(),
            'user_agent'         => substr((string) $request->userAgent(), 0, 255),
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $patientName = trim(($patient->nombres ?? '') . ' ' . ($patient->apellido_paterno ?? ''));

        app(AuditLogService::class)->record([
            'user_id'        => $request->user()?->id,
            'action'         => 'privacy_notice_accepted',
            'module'         => 'Pacientes',
            'auditable_type' => 'persona',
            'auditable_id'   => (int) $paciente,
            'human_message'  => 'El usuario ' . ($request->user()?->name ?? 'Desconocido') . ' registró aceptación del aviso de privacidad v' . $version . ' para "' . $patientName . '".',
            'new_values'     => ['version' => $version, 'accepted_at' => now()->toIso8601String()],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return back()->with('success', 'Aviso de privacidad registrado correctamente.');
    }
}
