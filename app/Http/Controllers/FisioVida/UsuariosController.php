<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use App\Mail\UserCredentialsMail;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Throwable;

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
                $builder
                    ->where('name', 'like', $q)
                    ->orWhere('email', 'like', $q);
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
                'roles' => $user->roles
                    ->map(fn ($role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                    ])
                    ->values(),
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
            'roles' => Role::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
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
        ], [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ya existe un usuario con este correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'status.required' => 'El estado del usuario es obligatorio.',
        ]);

        if (
            empty($data['is_super_admin']) &&
            empty($data['role_ids'])
        ) {
            return back()
                ->withErrors([
                    'role_ids' => 'Selecciona al menos un rol para el usuario.',
                ])
                ->withInput();
        }

        $plainPassword = $data['password'];

        /** @var User $user */
        $user = DB::transaction(function () use ($data) {
            $roleIds = $data['role_ids'] ?? [];
            unset($data['role_ids']);

            $data['password'] = Hash::make($data['password']);

            /** @var User $user */
            $user = User::query()->create($data);

            $user->roles()->sync($roleIds);

            app(AuditLogService::class)->created(
                request(),
                'Usuarios',
                'user',
                $user->id,
                'El usuario '.request()->user()?->name.' creó al usuario '.$user->name.'.',
                ['roles' => $roleIds] + $data
            );

            return $user->load('roles:id,name');
        });

        try {
            Mail::to($user->email)->send(
                new UserCredentialsMail(
                    user: $user,
                    plainPassword: $plainPassword,
                    loginUrl: url('/login')
                )
            );
        } catch (Throwable $exception) {
            Log::error('No se pudo enviar el correo de credenciales del usuario.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'message' => $exception->getMessage(),
            ]);

            return back()->with(
                'warning',
                'Usuario creado, pero no se pudo enviar el correo de credenciales. Revisa la configuración de correo.'
            );
        }

        return back()->with('success', 'Usuario creado y correo enviado correctamente.');
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
        ], [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ya existe un usuario con este correo.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'status.required' => 'El estado del usuario es obligatorio.',
        ]);

        if (
            empty($data['is_super_admin']) &&
            empty($data['role_ids'])
        ) {
            return back()
                ->withErrors([
                    'role_ids' => 'Selecciona al menos un rol para el usuario.',
                ])
                ->withInput();
        }

        DB::transaction(function () use ($data, $usuario) {
            $before = $usuario->toArray();

            $roleIds = $data['role_ids'] ?? [];
            unset($data['role_ids']);

            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $usuario->update($data);
            $usuario->roles()->sync($roleIds);

            app(AuditLogService::class)->updated(
                request(),
                'Usuarios',
                'user',
                $usuario->id,
                'El usuario '.request()->user()?->name.' actualizó al usuario '.$usuario->name.'.',
                $before,
                ['roles' => $roleIds] + $data
            );
        });

        return back()->with('success', 'Usuario actualizado.');
    }

    public function toggleStatus(User $usuario)
    {
        $from = $usuario->status;

        $usuario->update([
            'status' => $usuario->status === 'active' ? 'blocked' : 'active',
        ]);

        app(AuditLogService::class)->statusChanged(
            request(),
            'Usuarios',
            'user',
            $usuario->id,
            'El usuario '.request()->user()?->name.' cambió el estado del usuario '.$usuario->name.'.',
            $from,
            $usuario->status
        );

        return back()->with('success', 'Estado del usuario actualizado.');
    }

    public function destroy(User $usuario)
    {
        $before = $usuario->toArray();

        $usuario->delete();

        app(AuditLogService::class)->deleted(
            request(),
            'Usuarios',
            'user',
            $usuario->id,
            'El usuario '.request()->user()?->name.' eliminó al usuario '.$usuario->name.'.',
            $before
        );
        return back()->with('success', 'Usuario eliminado.');
    }

}
