<?php
// app/Http/Controllers/FisioVida/PacientesController.php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PacientesController extends Controller {

    use CrudHelpers;

    public function index(Request $request) {
        $q = $this->like($request->string('q'));
        $status = $request->string('status')->toString();
        $query = DB::table('personas as p')
            ->whereNull('p.deleted_at')
            ->whereIn('p.tipo', ['paciente','ambos'])
            ->select([
                'p.id','p.tipo','p.status','p.nombres','p.apellido_paterno','p.apellido_materno',
                'p.fecha_nacimiento','p.sexo','p.telefono','p.email','p.direccion',
                'p.contacto_emergencia_nombre','p.contacto_emergencia_telefono','p.notas',
                'p.created_at','p.updated_at'
            ])
            ->orderByDesc('p.id');
        if ($status !== '') $query->where('p.status', $status);
        if ($q) {
            $query->where(function($w) use ($q){
                $w->where('p.nombres','like',$q)
                    ->orWhere('p.apellido_paterno','like',$q)
                    ->orWhere('p.apellido_materno','like',$q)
                    ->orWhere('p.email','like',$q)
                    ->orWhere('p.telefono','like',$q);
            });
        }
        $p = $query->paginate(10)->withQueryString();
        $rows = collect($p->items())->map(function($r){
            $full = trim(implode(' ', array_filter([$r->nombres,$r->apellido_paterno,$r->apellido_materno])));
            return [
                'id' => $r->id,
                'full_name' => $full,
                'tipo' => $r->tipo,
                'status' => $r->status,
                'telefono' => $r->telefono,
                'email' => $r->email,
                'sexo' => $r->sexo,
                'fecha_nacimiento' => $r->fecha_nacimiento,
                'direccion' => $r->direccion,
                'contacto_emergencia_nombre' => $r->contacto_emergencia_nombre,
                'contacto_emergencia_telefono' => $r->contacto_emergencia_telefono,
                'notas' => $r->notas,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ];
        })->values();
        return Inertia::render('Pacientes/Index', [
            'rows' => $rows,
            'page' => $this->packPaginator($p),
            'filters' => $this->filters($request, ['q','status']),
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'status' => ['required','in:active,inactive'],
            'nombres' => ['required','string','max:120'],
            'apellido_paterno' => ['nullable','string','max:120'],
            'apellido_materno' => ['nullable','string','max:120'],
            'fecha_nacimiento' => ['nullable','date'],
            'sexo' => ['nullable','in:M,F,X'],
            'telefono' => ['nullable','string','max:30'],
            'email' => ['nullable','email','max:190'],
            'direccion' => ['nullable','string','max:255'],
            'contacto_emergencia_nombre' => ['nullable','string','max:190'],
            'contacto_emergencia_telefono' => ['nullable','string','max:30'],
            'notas' => ['nullable','string'],
        ]);
        $data['tipo'] = 'paciente';
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('personas')->insert($data);
        return back()->with('success', 'Paciente creado.');
    }

    public function update(Request $request, string $id) {
        $data = $request->validate([
            'status' => ['required','in:active,inactive'],
            'nombres' => ['required','string','max:120'],
            'apellido_paterno' => ['nullable','string','max:120'],
            'apellido_materno' => ['nullable','string','max:120'],
            'fecha_nacimiento' => ['nullable','date'],
            'sexo' => ['nullable','in:M,F,X'],
            'telefono' => ['nullable','string','max:30'],
            'email' => ['nullable','email','max:190'],
            'direccion' => ['nullable','string','max:255'],
            'contacto_emergencia_nombre' => ['nullable','string','max:190'],
            'contacto_emergencia_telefono' => ['nullable','string','max:30'],
            'notas' => ['nullable','string'],
        ]);
        $data['updated_at'] = now();
        DB::table('personas')->where('id',$id)->update($data);
        return back()->with('success', 'Paciente actualizado.');
    }

    public function destroy(string $id) {
        // soft delete
        DB::table('personas')->where('id',$id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        return back()->with('success', 'Paciente eliminado.');
    }

}
