<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use App\Mail\PatientWelcomeMail;
use App\Mail\UserCredentialsMail;
use App\Models\Persona;
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

class UsuariosController extends Controller {

    use CrudHelpers;

    public function index(Request $request) {
        $q = $this->like($request->string('q'));
        $status = $request->string('status')->toString();

        $query = User::query()
            ->with([
                'roles:id,name',
                'persona:id,tipo,status,nombres,apellido_paterno,apellido_materno,telefono,email,sexo,fecha_nacimiento,direccion',
            ])
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder
                    ->where('name', 'like', $q)
                    ->orWhere('email', 'like', $q)
                    ->orWhereHas('persona', function ($personaQuery) use ($q) {
                        $personaQuery
                            ->where('nombres', 'like', $q)
                            ->orWhere('apellido_paterno', 'like', $q)
                            ->orWhere('apellido_materno', 'like', $q)
                            ->orWhere('telefono', 'like', $q)
                            ->orWhere('email', 'like', $q);
                    });
            });
        }

        $p = $query->paginate(10)->withQueryString();

        $rows = collect($p->items())->map(function (User $user) {
            $persona = $user->persona;

            return [
                'id' => $user->id,
                'persona_id' => $user->persona_id,

                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'is_super_admin' => (bool) $user->is_super_admin,

                'persona_tipo' => $persona?->tipo,
                'persona_status' => $persona?->status,
                'nombres' => $persona?->nombres ?? '',
                'apellido_paterno' => $persona?->apellido_paterno ?? '',
                'apellido_materno' => $persona?->apellido_materno ?? '',
                'telefono' => $persona?->telefono ?? '',
                'persona_email' => $persona?->email ?? $user->email,
                'sexo' => $persona?->sexo,
                'fecha_nacimiento' => $persona?->fecha_nacimiento,
                'direccion' => $persona?->direccion,

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

    public function store(Request $request) {
        $data = $request->validate([
            'nombres' => ['required', 'string', 'max:120'],
            'apellido_paterno' => ['nullable', 'string', 'max:120'],
            'apellido_materno' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
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
            'nombres.required' => 'El nombre de la persona es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ya existe un usuario con este correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'status.required' => 'El estado del usuario es obligatorio.',
        ]);

        if (empty($data['is_super_admin']) && empty($data['role_ids'])) {
            return back()
                ->withErrors([
                    'role_ids' => 'Selecciona al menos un rol para el usuario.',
                ])
                ->withInput();
        }

        /** @var User $user */
        $user = DB::transaction(function () use ($data) {
            $roleIds = $data['role_ids'] ?? [];

            $personaStatus = $data['status'] === 'active' ? 'active' : 'inactive';

            /** @var Persona $persona */
            $persona = Persona::query()->create([
                'tipo' => 'staff',
                'status' => $personaStatus,
                'nombres' => trim($data['nombres']),
                'apellido_paterno' => $data['apellido_paterno'] ? trim($data['apellido_paterno']) : null,
                'apellido_materno' => $data['apellido_materno'] ? trim($data['apellido_materno']) : null,
                'telefono' => $data['telefono'] ? trim($data['telefono']) : null,
                'email' => trim($data['email']),
            ]);

            $fullName = $this->buildFullName(
                $persona->nombres,
                $persona->apellido_paterno,
                $persona->apellido_materno
            );

            /** @var User $user */
            $user = User::query()->create([
                'persona_id' => $persona->id,
                'name' => $fullName,
                'email' => trim($data['email']),
                'password' => Hash::make($data['password']),
                'status' => $data['status'],
                'is_super_admin' => (bool) ($data['is_super_admin'] ?? false),

                'mod_agenda' => (bool) ($data['mod_agenda'] ?? false),
                'mod_pacientes' => (bool) ($data['mod_pacientes'] ?? false),
                'mod_sesiones' => (bool) ($data['mod_sesiones'] ?? false),
                'mod_ejercicios' => (bool) ($data['mod_ejercicios'] ?? false),
                'mod_archivos' => (bool) ($data['mod_archivos'] ?? false),
                'mod_reportes' => (bool) ($data['mod_reportes'] ?? false),
                'mod_cobranza' => (bool) ($data['mod_cobranza'] ?? false),
                'mod_config' => (bool) ($data['mod_config'] ?? false),
            ]);

            $user->roles()->sync($roleIds);

            app(AuditLogService::class)->created(
                request(),
                'Usuarios',
                'user',
                $user->id,
                'El usuario '.request()->user()?->name.' creó al usuario '.$user->name.'.',
                [
                    'persona_id' => $persona->id,
                    'roles' => $roleIds,
                ]
            );

            return $user->load('roles:id,name', 'persona');
        });

        try {
            $token = app('auth.password.broker')->createToken($user);
            $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($user->email));

            $isPatient = Role::whereIn('id', $roleIds)->where('slug', 'paciente')->exists();

            $clinicName = \Illuminate\Support\Facades\DB::table('system_settings')
                ->where('key', 'clinic_name')
                ->value('value') ?? 'FisioVida';

            if ($isPatient) {
                Mail::to($user->email)->send(new PatientWelcomeMail(
                    user: $user,
                    portalUrl: url('/mi-portal'),
                    resetUrl: $resetUrl,
                    clinicName: $clinicName,
                ));
            } else {
                Mail::to($user->email)->send(new UserCredentialsMail(
                    user: $user,
                    loginUrl: url('/login'),
                    resetUrl: $resetUrl,
                ));
            }
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

    public function update(Request $request, User $usuario) {
        $data = $request->validate([
            'nombres' => ['required', 'string', 'max:120'],
            'apellido_paterno' => ['nullable', 'string', 'max:120'],
            'apellido_materno' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
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
            'nombres.required' => 'El nombre de la persona es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ya existe un usuario con este correo.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'status.required' => 'El estado del usuario es obligatorio.',
        ]);

        if (empty($data['is_super_admin']) && empty($data['role_ids'])) {
            return back()
                ->withErrors([
                    'role_ids' => 'Selecciona al menos un rol para el usuario.',
                ])
                ->withInput();
        }

        DB::transaction(function () use ($data, $usuario) {
            $before = $usuario->load('persona')->toArray();

            $roleIds = $data['role_ids'] ?? [];
            $personaStatus = $data['status'] === 'active' ? 'active' : 'inactive';

            $persona = $usuario->persona;

            if (! $persona) {
                $persona = Persona::query()->create([
                    'tipo' => 'staff',
                    'status' => $personaStatus,
                    'nombres' => trim($data['nombres']),
                    'apellido_paterno' => $data['apellido_paterno'] ? trim($data['apellido_paterno']) : null,
                    'apellido_materno' => $data['apellido_materno'] ? trim($data['apellido_materno']) : null,
                    'telefono' => $data['telefono'] ? trim($data['telefono']) : null,
                    'email' => trim($data['email']),
                ]);

                $usuario->persona_id = $persona->id;
            } else {
                $persona->update([
                    'tipo' => $persona->tipo === 'paciente' ? 'ambos' : $persona->tipo,
                    'status' => $personaStatus,
                    'nombres' => trim($data['nombres']),
                    'apellido_paterno' => $data['apellido_paterno'] ? trim($data['apellido_paterno']) : null,
                    'apellido_materno' => $data['apellido_materno'] ? trim($data['apellido_materno']) : null,
                    'telefono' => $data['telefono'] ? trim($data['telefono']) : null,
                    'email' => trim($data['email']),
                ]);
            }

            $fullName = $this->buildFullName(
                $data['nombres'],
                $data['apellido_paterno'] ?? null,
                $data['apellido_materno'] ?? null
            );

            $userPayload = [
                'persona_id' => $persona->id,
                'name' => $fullName,
                'email' => trim($data['email']),
                'status' => $data['status'],
                'is_super_admin' => (bool) ($data['is_super_admin'] ?? false),

                'mod_agenda' => (bool) ($data['mod_agenda'] ?? false),
                'mod_pacientes' => (bool) ($data['mod_pacientes'] ?? false),
                'mod_sesiones' => (bool) ($data['mod_sesiones'] ?? false),
                'mod_ejercicios' => (bool) ($data['mod_ejercicios'] ?? false),
                'mod_archivos' => (bool) ($data['mod_archivos'] ?? false),
                'mod_reportes' => (bool) ($data['mod_reportes'] ?? false),
                'mod_cobranza' => (bool) ($data['mod_cobranza'] ?? false),
                'mod_config' => (bool) ($data['mod_config'] ?? false),
            ];

            if (! empty($data['password'])) {
                $userPayload['password'] = Hash::make($data['password']);
            }

            $usuario->update($userPayload);
            $usuario->roles()->sync($roleIds);

            app(AuditLogService::class)->updated(
                request(),
                'Usuarios',
                'user',
                $usuario->id,
                'El usuario '.request()->user()?->name.' actualizó al usuario '.$usuario->name.'.',
                $before,
                [
                    'persona_id' => $persona->id,
                    'roles' => $roleIds,
                ] + $userPayload
            );
        });

        return back()->with('success', 'Usuario actualizado.');
    }

    public function toggleStatus(User $usuario) {
        $from = $usuario->status;
        $to = $usuario->status === 'active' ? 'blocked' : 'active';

        $usuario->update([
            'status' => $to,
        ]);

        if ($usuario->persona) {
            $usuario->persona->update([
                'status' => $to === 'active' ? 'active' : 'inactive',
            ]);
        }
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

    public function destroy(User $usuario) {
        $before = $usuario->load('persona')->toArray();
        DB::transaction(function () use ($usuario, $before) {
            $usuario->delete();

            if ($usuario->persona) {
                $usuario->persona->update([
                    'status' => 'inactive',
                ]);
            }
            app(AuditLogService::class)->deleted(
                request(),
                'Usuarios',
                'user',
                $usuario->id,
                'El usuario '.request()->user()?->name.' eliminó al usuario '.$usuario->name.'.',
                $before
            );
        });
        return back()->with('success', 'Usuario eliminado.');
    }

    private function buildFullName(string $nombres, ?string $apellidoPaterno, ?string $apellidoMaterno): string
    {
        return trim(collect([
            $nombres,
            $apellidoPaterno,
            $apellidoMaterno,
        ])->filter()->implode(' '));
    }

}
