<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use App\Http\Requests\Pacientes\PacienteStoreRequest;
use App\Http\Requests\Pacientes\PacienteUpdateRequest;
use App\Http\Resources\PacienteResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class PacientesController extends Controller
{
    use CrudHelpers;

    public function index(Request $request)
    {
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

        $paginator = $query->paginate(10)->withQueryString();

        return Inertia::render('Pacientes/Index', [
            'rows' => PacienteResource::collection(collect($paginator->items()))->resolve(),
            'page' => $this->packPaginator($paginator),
            'filters' => $this->filters($request, ['q', 'status']),
        ]);
    }

    public function store(PacienteStoreRequest $request)
    {
        $payload = $this->sanitizePersonaPayload($request->validated());
        $payload['tipo'] = 'paciente';
        $payload['created_at'] = now();
        $payload['updated_at'] = now();

        DB::table('personas')->insert($payload);

        return back()->with('success', 'Paciente creado.');
    }

    public function update(PacienteUpdateRequest $request, string $id)
    {
        $payload = $this->sanitizePersonaPayload($request->validated());
        $payload['updated_at'] = now();

        DB::table('personas')->where('id', $id)->update($payload);

        return back()->with('success', 'Paciente actualizado.');
    }

    public function destroy(string $id)
    {
        DB::table('personas')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Paciente eliminado.');
    }

    private function sanitizePersonaPayload(array $payload): array
    {
        $columns = array_flip(Schema::getColumnListing('personas'));

        return array_intersect_key($payload, $columns);
    }
}
