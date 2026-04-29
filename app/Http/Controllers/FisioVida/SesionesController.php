<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use App\Http\Requests\Sesiones\SesionStoreRequest;
use App\Http\Requests\Sesiones\SesionUpdateRequest;
use App\Http\Resources\SesionResource;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SesionesController extends Controller
{
    use CrudHelpers;

    public function index(Request $request)
    {
        $q = $this->like($request->string('q'));
        $perPageInput = $request->input('per_page', 10);

        $query = DB::table('therapy_sessions as s')
            ->join('personas as p', 'p.id', '=', 's.patient_persona_id')
            ->join('users as u', 'u.id', '=', 's.therapist_user_id')
            ->whereNull('p.deleted_at')
            ->whereNull('u.deleted_at')
            ->select([
                's.id',
                's.appointment_id',
                's.patient_persona_id',
                's.therapist_user_id',
                's.session_date',
                's.subjective',
                's.objective',
                's.assessment',
                's.plan',
                's.pain_scale',
                's.notes',
                DB::raw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) as patient_name"),
                'u.name as therapist_name',
                's.created_at',
                's.updated_at',
            ])
            ->orderByDesc('s.session_date')
            ->orderByDesc('s.id');

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('p.nombres', 'like', $q)
                    ->orWhere('p.apellido_paterno', 'like', $q)
                    ->orWhere('p.apellido_materno', 'like', $q)
                    ->orWhere('u.name', 'like', $q)
                    ->orWhere('s.subjective', 'like', $q)
                    ->orWhere('s.objective', 'like', $q)
                    ->orWhere('s.assessment', 'like', $q)
                    ->orWhere('s.plan', 'like', $q)
                    ->orWhere('s.notes', 'like', $q);
            });
        }

        if ($perPageInput === 'all') {
            $total = (clone $query)->count();
            $perPage = max(1, min($total, 500));
        } else {
            $perPage = (int) $perPageInput;
            $perPage = in_array($perPage, [10, 15, 20, 50], true)
                ? $perPage
                : 10;
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $patients = DB::table('personas')
            ->whereNull('deleted_at')
            ->whereIn('tipo', ['paciente', 'ambos'])
            ->where('status', 'active')
            ->orderBy('apellido_paterno')
            ->limit(300)
            ->get([
                'id',
                DB::raw("TRIM(CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno)) as label"),
            ]);

        $therapists = DB::table('users')
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(300)
            ->get([
                'id',
                DB::raw('name as label'),
            ]);

        $appointments = DB::table('appointments as a')
            ->join('personas as p', 'p.id', '=', 'a.patient_persona_id')
            ->join('users as u', 'u.id', '=', 'a.therapist_user_id')
            ->whereNull('p.deleted_at')
            ->whereNull('u.deleted_at')
            ->whereIn('a.status', ['confirmed', 'arrived', 'done', 'scheduled'])
            ->orderByDesc('a.start_at')
            ->limit(300)
            ->get([
                'a.id',
                'a.patient_persona_id',
                'a.therapist_user_id',
                'a.start_at',
                'a.end_at',
                'a.status',
                DB::raw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) as patient_name"),
                'u.name as therapist_name',
                DB::raw("CONCAT('#', a.id, ' • ', TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)), ' • ', u.name, ' • ', DATE_FORMAT(a.start_at, '%Y-%m-%d %H:%i')) as label"),
            ]);

        return Inertia::render('Sesiones/Index', [
            'rows' => SesionResource::collection(collect($paginator->items()))->resolve(),
            'page' => [
                ...$this->packPaginator($paginator),
                'per_page_selected' => $perPageInput === 'all' ? 'all' : $perPage,
            ],
            'filters' => $this->filters($request, ['q', 'per_page']),
            'lookups' => [
                'patients' => $patients,
                'therapists' => $therapists,
                'appointments' => $appointments,
            ],
        ]);
    }

    public function store(SesionStoreRequest $request)
    {
        $payload = $request->validated();

        $payload['appointment_id'] = $payload['appointment_id'] ?: null;
        $payload['pain_scale'] = $payload['pain_scale'] === '' ? null : $payload['pain_scale'];
        $payload['created_at'] = now();
        $payload['updated_at'] = now();

        DB::table('therapy_sessions')->insert($payload);

        $id = (int) DB::getPdo()->lastInsertId();

        app(AuditLogService::class)->created(
            $request,
            'Sesiones',
            'therapy_session',
            $id,
            'El usuario '.$request->user()?->name.' registró una sesión clínica.',
            $payload,
        );

        return back()->with('success', 'Sesión creada.');
    }

    public function update(SesionUpdateRequest $request, string $id)
    {
        $old = (array) DB::table('therapy_sessions')->where('id', $id)->first();

        abort_if(empty($old), 404);

        $payload = $request->validated();

        $payload['appointment_id'] = $payload['appointment_id'] ?: null;
        $payload['pain_scale'] = $payload['pain_scale'] === '' ? null : $payload['pain_scale'];
        $payload['updated_at'] = now();

        DB::table('therapy_sessions')->where('id', $id)->update($payload);

        app(AuditLogService::class)->updated(
            $request,
            'Sesiones',
            'therapy_session',
            (int) $id,
            'El usuario '.$request->user()?->name.' actualizó una sesión clínica.',
            $old,
            $payload,
        );

        return back()->with('success', 'Sesión actualizada.');
    }

    public function destroy(Request $request, string $id)
    {
        $old = (array) DB::table('therapy_sessions')->where('id', $id)->first();

        abort_if(empty($old), 404);

        DB::transaction(function () use ($request, $id, $old) {
            DB::table('session_exercises')
                ->where('session_id', (int) $id)
                ->delete();

            DB::table('therapy_sessions')
                ->where('id', $id)
                ->delete();

            app(AuditLogService::class)->deleted(
                $request,
                'Sesiones',
                'therapy_session',
                (int) $id,
                'El usuario '.$request->user()?->name.' eliminó una sesión clínica.',
                $old,
            );
        });

        return back()->with('success', 'Sesión eliminada.');
    }
}
