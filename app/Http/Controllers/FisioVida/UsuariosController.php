<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UsuariosController extends Controller
{
    use CrudHelpers;

    public function index(Request $request)
    {
        $q = $this->like($request->string('q'));
        $status = $request->string('status')->toString();

        $query = User::query()
            ->with(['roles:id,name'])
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', $q)->orWhere('email', 'like', $q);
            });
        }

        $p = $query->paginate(10)->withQueryString();
        $rows = collect($p->items())->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'is_super_admin' => (bool) $user->is_super_admin,
                'roles' => $user->roles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])->values(),
                'role_ids' => $user->roles->pluck('id')->values(),
                'mod_agenda' => (bool) $user->mod_agenda,
                'mod_pacientes' => (bool) $user->mod_pacientes,
                'mod_sesiones' => (bool) $user->mod_sesiones,
                'mod_ejercicios' => (bool) $user->mod_ejercicios,
                'mod_archivos' => (bool) $user->mod_archivos,
                'mod_reportes' => (bool) $user->mod_reportes,
                'mod_cobranza' => (bool) $user->mod_cobranza,
                'mod_config' => (bool) $user->mod_config,
                'last_login_at' => $user->last_login_at,
                'last_login_ip' => $user->last_login_ip,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        })->values();

        return Inertia::render('Usuarios/Index', [
            'rows' => $rows,
            'page' => $this->packPaginator($p),
            'filters' => $this->filters($request, ['q', 'status']),
            'roles' => Role::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'status' => ['required', 'in:active,blocked'],
            'is_super_admin' => ['boolean'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],

            'mod_agenda' => ['boolean'],
            'mod_pacientes' => ['boolean'],
            'mod_sesiones' => ['boolean'],
            'mod_ejercicios' => ['boolean'],
            'mod_archivos' => ['boolean'],
            'mod_reportes' => ['boolean'],
            'mod_cobranza' => ['boolean'],
            'mod_config' => ['boolean'],
        ]);

        DB::transaction(function () use ($data) {
            $roleIds = $data['role_ids'] ?? [];
            unset($data['role_ids']);
            $data['password'] = Hash::make($data['password']);

            /** @var User $user */
            $user = User::query()->create($data);
            $user->roles()->sync($roleIds);
        });

        return back()->with('success', 'Usuario creado.');
    }

    public function update(Request $request, User $usuario)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', "unique:users,email,{$usuario->id}"],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'status' => ['required', 'in:active,blocked'],
            'is_super_admin' => ['boolean'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],

            'mod_agenda' => ['boolean'],
            'mod_pacientes' => ['boolean'],
            'mod_sesiones' => ['boolean'],
            'mod_ejercicios' => ['boolean'],
            'mod_archivos' => ['boolean'],
            'mod_reportes' => ['boolean'],
            'mod_cobranza' => ['boolean'],
            'mod_config' => ['boolean'],
        ]);

        DB::transaction(function () use ($data, $usuario) {
            $roleIds = $data['role_ids'] ?? [];
            unset($data['role_ids']);

            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $usuario->update($data);
            $usuario->roles()->sync($roleIds);
        });

        return back()->with('success', 'Usuario actualizado.');
    }

    public function toggleStatus(User $usuario)
    {
        $usuario->update([
            'status' => $usuario->status === 'active' ? 'blocked' : 'active',
        ]);

        return back()->with('success', 'Estado del usuario actualizado.');
    }

    public function destroy(User $usuario)
    {
        $usuario->delete();

        return back()->with('success', 'Usuario eliminado.');
    }
}
