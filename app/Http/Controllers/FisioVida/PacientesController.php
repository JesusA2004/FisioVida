<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use App\Http\Requests\Pacientes\PacienteStoreRequest;
use App\Http\Requests\Pacientes\PacienteUpdateRequest;
use App\Http\Resources\PacienteResource;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class PacientesController extends Controller {

    use CrudHelpers;

    public function index(Request $request) {
        $q = $this->like($request->string('q'));
        $status = $request->string('status')->toString();
        $hasEmail = Schema::hasColumn('personas', 'email');

        $select = [
            'p.id', 'p.tipo', 'p.status', 'p.nombres', 'p.apellido_paterno', 'p.apellido_materno',
            'p.fecha_nacimiento', 'p.sexo', 'p.telefono', 'p.direccion',
            'p.contacto_emergencia_nombre', 'p.contacto_emergencia_telefono', 'p.notas',
            'p.created_at', 'p.updated_at',
        ];
        if ($hasEmail) {
            $select[] = 'p.email';
        }

        $query = DB::table('personas as p')
            ->whereNull('p.deleted_at')
            ->whereIn('p.tipo', ['paciente', 'ambos'])
            ->select($select)
            ->orderByDesc('p.id');

        if ($status !== '') {
            $query->where('p.status', $status);
        }

        if ($q) {
            $query->where(function ($w) use ($q, $hasEmail) {
                $w->where('p.nombres', 'like', $q)
                    ->orWhere('p.apellido_paterno', 'like', $q)
                    ->orWhere('p.apellido_materno', 'like', $q)
                    ->orWhere('p.telefono', 'like', $q);
                if ($hasEmail) {
                    $w->orWhere('p.email', 'like', $q);
                }
            });
        }

        $perPageInput = $request->input('per_page', 10);

        $perPage = $perPageInput === 'all'
            ? max(1, min((int) $query->count(), 500))
            : (int) $perPageInput;

        $perPage = in_array($perPage, [10, 15, 20, 50], true)
            ? $perPage
            : 10;

        if ($perPageInput === 'all') {
            $total = (clone $query)->count();
            $perPage = max(1, min($total, 500));
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Pacientes/Index', [
            'rows' => PacienteResource::collection(collect($paginator->items()))->resolve(),
            'page' => [
                ...$this->packPaginator($paginator),
                'per_page_selected' => $perPageInput === 'all' ? 'all' : $perPage,
            ],
            'filters' => $this->filters($request, ['q', 'status', 'per_page']),
        ]);
    }

    public function store(PacienteStoreRequest $request) {
        $payload = $this->sanitizePersonaPayload($request->validated());
        $payload['tipo'] = 'paciente';
        $payload['created_at'] = now();
        $payload['updated_at'] = now();

        DB::table('personas')->insert($payload);
        $newId = (int) DB::getPdo()->lastInsertId();
        app(AuditLogService::class)->created(
            $request,
            'Pacientes',
            'persona',
            $newId,
            'El usuario '.$request->user()?->name.' registró al paciente '.trim(($payload['nombres'] ?? '').' '.($payload['apellido_paterno'] ?? '').' '.($payload['apellido_materno'] ?? '')).'.',
            $payload,
        );

        return back()->with('success', 'Paciente creado.');
    }

    public function update(PacienteUpdateRequest $request, string $paciente)
    {
        $id = $paciente;
        $old = (array) DB::table('personas')->where('id', $id)->first();
        $payload = $this->sanitizePersonaPayload($request->validated());
        $payload['updated_at'] = now();

        DB::table('personas')->where('id', $id)->update($payload);
        app(AuditLogService::class)->updated(
            $request,
            'Pacientes',
            'persona',
            (int) $id,
            'El usuario '.$request->user()?->name.' editó la ficha del paciente '.trim(($old['nombres'] ?? '').' '.($old['apellido_paterno'] ?? '').' '.($old['apellido_materno'] ?? '')).'.',
            $old,
            $payload,
        );

        return back()->with('success', 'Paciente actualizado.');
    }

    public function destroy(Request $request, string $paciente)
    {
        $id = (int) $paciente;

        $old = (array) DB::table('personas')
            ->where('id', $id)
            ->whereIn('tipo', ['paciente', 'ambos'])
            ->whereNull('deleted_at')
            ->first();

        abort_if(empty($old), 404);

        DB::table('personas')
            ->where('id', $id)
            ->update([
                'status' => 'inactive',
                'updated_at' => now(),
            ]);

        app(AuditLogService::class)->updated(
            $request,
            'Pacientes',
            'persona',
            $id,
            'El usuario '.$request->user()?->name.' desactivó al paciente '.trim(($old['nombres'] ?? '').' '.($old['apellido_paterno'] ?? '').' '.($old['apellido_materno'] ?? '')).'.',
            $old,
            [
                ...$old,
                'status' => 'inactive',
                'updated_at' => now()->toDateTimeString(),
            ],
        );

        return back()->with('success', 'Paciente desactivado.');
    }
    
        public function activate(Request $request, string $paciente)
    {
        $id = (int) $paciente;

        $old = (array) DB::table('personas')
            ->where('id', $id)
            ->whereIn('tipo', ['paciente', 'ambos'])
            ->whereNull('deleted_at')
            ->first();

        abort_if(empty($old), 404);

        DB::table('personas')
            ->where('id', $id)
            ->update([
                'status' => 'active',
                'updated_at' => now(),
            ]);

        app(AuditLogService::class)->updated(
            $request,
            'Pacientes',
            'persona',
            $id,
            'El usuario '.$request->user()?->name.' activó al paciente '.trim(($old['nombres'] ?? '').' '.($old['apellido_paterno'] ?? '').' '.($old['apellido_materno'] ?? '')).'.',
            $old,
            [
                ...$old,
                'status' => 'active',
                'updated_at' => now()->toDateTimeString(),
            ],
        );

        return back()->with('success', 'Paciente activado.');
    }

    public function show(string $paciente) {
        $id = $paciente;
        $hasEmail = Schema::hasColumn('personas', 'email');
        $patient = DB::table('personas')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->select([
                'id', 'status', 'nombres', 'apellido_paterno', 'apellido_materno', 'fecha_nacimiento',
                'sexo', 'telefono', 'direccion', 'contacto_emergencia_nombre', 'contacto_emergencia_telefono',
                'notas', 'created_at', 'updated_at',
                ...($hasEmail ? ['email'] : []),
            ])
            ->first();

        abort_if(! $patient, 404);

        $appointments = DB::table('appointments as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.therapist_user_id')
            ->where('a.patient_persona_id', $id)
            ->orderByDesc('a.start_at')
            ->limit(20)
            ->get(['a.id', 'a.status', 'a.start_at', 'a.end_at', 'u.name as therapist_name']);

        $sessions = DB::table('therapy_sessions as s')
            ->leftJoin('users as u', 'u.id', '=', 's.therapist_user_id')
            ->where('s.patient_persona_id', $id)
            ->orderByDesc('s.session_date')
            ->limit(20)
            ->get(['s.id', 's.session_date', 's.pain_scale', 's.assessment', 's.plan', 'u.name as therapist_name']);

        $files = DB::table('files as f')
            ->leftJoin('therapy_sessions as s', 's.id', '=', 'f.session_id')
            ->leftJoin('users as u', 'u.id', '=', 'f.uploaded_by')
            ->where(function ($where) use ($id) {
                $where->where('f.patient_persona_id', $id)
                    ->orWhere('s.patient_persona_id', $id);
            })
            ->orderByDesc('f.id')
            ->limit(20)
            ->get([
                'f.id',
                'f.original_name',
                'f.file_type',
                'f.mime',
                'f.created_at',
                'f.session_id',
                's.session_date',
                'u.name as uploaded_by_name',
            ])
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'file_type' => $file->file_type,
                    'mime' => $file->mime,
                    'created_at' => $file->created_at,
                    'session_id' => $file->session_id,
                    'session_date' => $file->session_date,
                    'uploaded_by_name' => $file->uploaded_by_name,
                    'preview_url' => route('archivos.show', $file->id),
                    'download_url' => route('archivos.download', $file->id),
                ];
            })
            ->values();

        $payments = DB::table('payments')
            ->whereNotNull('reference')
            ->where('reference', 'like', '%'.$id.'%')
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'amount', 'currency', 'status', 'paid_at', 'reference']);

        $activities = DB::table('activities as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.responsible_user_id')
            ->where('a.patient_persona_id', $id)
            ->orderByDesc('a.id')
            ->limit(20)
            ->get(['a.id', 'a.title', 'a.status', 'a.priority', 'a.due_date', 'u.name as responsible_name']);

        return Inertia::render('Pacientes/Show', [
            'patient' => [
                'id' => $patient->id,
                'full_name' => trim(($patient->nombres ?? '').' '.($patient->apellido_paterno ?? '').' '.($patient->apellido_materno ?? '')),
                'status' => $patient->status,
                'telefono' => $patient->telefono,
                'email' => $patient->email ?? null,
                'direccion' => $patient->direccion,
                'fecha_nacimiento' => $patient->fecha_nacimiento,
                'sexo' => $patient->sexo,
                'contacto_emergencia_nombre' => $patient->contacto_emergencia_nombre,
                'contacto_emergencia_telefono' => $patient->contacto_emergencia_telefono,
                'notas' => $patient->notas,
                'created_at' => $patient->created_at,
                'updated_at' => $patient->updated_at,
            ],
            'appointments' => $appointments,
            'sessions' => $sessions,
            'files' => $files,
            'payments' => $payments,
            'activities' => $activities,
        ]);
    }

    private function sanitizePersonaPayload(array $payload): array
    {
        $columns = array_flip(Schema::getColumnListing('personas'));

        return array_intersect_key($payload, $columns);
    }
}
