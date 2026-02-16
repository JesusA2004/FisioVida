<?php
// app/Http/Controllers/FisioVida/EjerciciosController.php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EjerciciosController extends Controller {

    use CrudHelpers;

    public function index(Request $request) {
        $q = $this->like($request->string('q'));
        $active = $request->string('active')->toString();
        $query = DB::table('exercises as e')
            ->select(['e.id','e.name','e.description','e.video_url','e.is_active','e.created_at','e.updated_at'])
            ->orderByDesc('e.id');
        if ($active !== '') $query->where('e.is_active', (int)$active);
        if ($q) $query->where('e.name','like',$q);
        $p = $query->paginate(10)->withQueryString();
        $rows = collect($p->items())->values();
        return Inertia::render('Ejercicios/Index', [
            'rows' => $rows,
            'page' => $this->packPaginator($p),
            'filters' => $this->filters($request, ['q','active']),
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => ['required','string','max:190'],
            'description' => ['nullable','string'],
            'video_url' => ['nullable','string','max:255'],
            'is_active' => ['required','boolean'],
        ]);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('exercises')->insert($data);
        return back()->with('success', 'Ejercicio creado.');
    }

    public function update(Request $request, string $id) {
        $data = $request->validate([
            'name' => ['required','string','max:190'],
            'description' => ['nullable','string'],
            'video_url' => ['nullable','string','max:255'],
            'is_active' => ['required','boolean'],
        ]);
        $data['updated_at'] = now();
        DB::table('exercises')->where('id',$id)->update($data);
        return back()->with('success', 'Ejercicio actualizado.');
    }

    public function destroy(string $id) {
        DB::table('exercises')->where('id',$id)->delete();
        return back()->with('success', 'Ejercicio eliminado.');
    }

}
