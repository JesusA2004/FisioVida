<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use App\Http\Requests\Ejercicios\EjercicioStoreRequest;
use App\Http\Requests\Ejercicios\EjercicioUpdateRequest;
use App\Http\Resources\EjercicioResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EjerciciosController extends Controller
{
    use CrudHelpers;

    public function index(Request $request)
    {
        $q = $this->like($request->string('q'));
        $active = $request->string('active')->toString();

        $query = DB::table('exercises as e')
            ->select(['e.id', 'e.name', 'e.description', 'e.video_url', 'e.is_active', 'e.created_at', 'e.updated_at'])
            ->orderByDesc('e.id');

        if ($active !== '') {
            $query->where('e.is_active', (int) $active);
        }

        if ($q) {
            $query->where('e.name', 'like', $q);
        }

        $paginator = $query->paginate(10)->withQueryString();

        return Inertia::render('Ejercicios/Index', [
            'rows' => EjercicioResource::collection(collect($paginator->items()))->resolve(),
            'page' => $this->packPaginator($paginator),
            'filters' => $this->filters($request, ['q', 'active']),
        ]);
    }

    public function store(EjercicioStoreRequest $request)
    {
        $payload = $request->validated();
        $payload['created_at'] = now();
        $payload['updated_at'] = now();

        DB::table('exercises')->insert($payload);

        return back()->with('success', 'Ejercicio creado.');
    }

    public function update(EjercicioUpdateRequest $request, string $id)
    {
        $payload = $request->validated();
        $payload['updated_at'] = now();

        DB::table('exercises')->where('id', $id)->update($payload);

        return back()->with('success', 'Ejercicio actualizado.');
    }

    public function destroy(string $id)
    {
        DB::table('exercises')->where('id', $id)->delete();

        return back()->with('success', 'Ejercicio eliminado.');
    }
}
