<?php
// app/Http/Controllers/FisioVida/UsuariosController.php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UsuariosController extends Controller {
    
    use CrudHelpers;

    public function index(Request $request) {
        $q = $this->like($request->string('q'));
        $status = $request->string('status')->toString();
        $query = DB::table('users as u')
            ->leftJoin('personas as p', 'p.id', '=', 'u.persona_id')
            ->whereNull('u.deleted_at')
            ->select([
                'u.id','u.name','u.email','u.status','u.is_super_admin',
                'u.mod_agenda','u.mod_pacientes','u.mod_sesiones','u.mod_ejercicios','u.mod_archivos',
                'u.mod_reportes','u.mod_cobranza','u.mod_config',
                'u.last_login_at','u.last_login_ip','u.created_at','u.updated_at',
                DB::raw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) as persona_full_name"),
            ])
            ->orderByDesc('u.id');
        if ($status !== '') $query->where('u.status', $status);
        if ($q) {
            $query->where(function($w) use ($q){
                $w->where('u.name','like',$q)->orWhere('u.email','like',$q);
            });
        }
        $p = $query->paginate(10)->withQueryString();
        $rows = collect($p->items())->map(function($r){
            return [
                'id' => $r->id,
                'name' => $r->name,
                'email' => $r->email,
                'status' => $r->status,
                'is_super_admin' => (bool)$r->is_super_admin,
                'mods' => [
                    'agenda' => (bool)$r->mod_agenda,
                    'pacientes' => (bool)$r->mod_pacientes,
                    'sesiones' => (bool)$r->mod_sesiones,
                    'ejercicios' => (bool)$r->mod_ejercicios,
                    'archivos' => (bool)$r->mod_archivos,
                    'reportes' => (bool)$r->mod_reportes,
                    'cobranza' => (bool)$r->mod_cobranza,
                    'config' => (bool)$r->mod_config,
                ],
                'persona_id' => null,
                'persona_full_name' => $r->persona_full_name,
                'last_login_at' => $r->last_login_at,
                'last_login_ip' => $r->last_login_ip,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ];
        })->values();
        return Inertia::render('Usuarios/Index', [
            'rows' => $rows,
            'page' => $this->packPaginator($p),
            'filters' => $this->filters($request, ['q','status']),
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => ['required','string','max:190'],
            'email' => ['required','email','max:190','unique:users,email'],
            'password' => ['required','string','min:8','max:255'],
            'status' => ['required','in:active,blocked'],
            'is_super_admin' => ['boolean'],

            'mod_agenda' => ['boolean'],
            'mod_pacientes' => ['boolean'],
            'mod_sesiones' => ['boolean'],
            'mod_ejercicios' => ['boolean'],
            'mod_archivos' => ['boolean'],
            'mod_reportes' => ['boolean'],
            'mod_cobranza' => ['boolean'],
            'mod_config' => ['boolean'],
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('users')->insert($data);
        return back()->with('success', 'Usuario creado.');
    }

    public function update(Request $request, string $id) {
        $data = $request->validate([
            'name' => ['required','string','max:190'],
            'email' => ['required','email','max:190',"unique:users,email,$id"],
            'password' => ['nullable','string','min:8','max:255'],
            'status' => ['required','in:active,blocked'],
            'is_super_admin' => ['boolean'],

            'mod_agenda' => ['boolean'],
            'mod_pacientes' => ['boolean'],
            'mod_sesiones' => ['boolean'],
            'mod_ejercicios' => ['boolean'],
            'mod_archivos' => ['boolean'],
            'mod_reportes' => ['boolean'],
            'mod_cobranza' => ['boolean'],
            'mod_config' => ['boolean'],
        ]);

        if (!empty($data['password'])) $data['password'] = Hash::make($data['password']);
        else unset($data['password']);
        $data['updated_at'] = now();
        DB::table('users')->where('id',$id)->update($data);
        return back()->with('success', 'Usuario actualizado.');
    }

    public function destroy(string $id) {
        DB::table('users')->where('id',$id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        return back()->with('success', 'Usuario eliminado.');
    }

}
