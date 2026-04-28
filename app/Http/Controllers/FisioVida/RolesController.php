<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\RoleStoreRequest;
use App\Http\Requests\Roles\RoleUpdateRequest;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RolesController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->string('q'));
        $status = trim((string) $request->string('status'));

        $query = Role::query()->withCount('permissions')->with('permissions:id,name,slug,module')->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $page = $query->paginate(10)->withQueryString();

        return Inertia::render('Roles/Index', [
            'rows' => RoleResource::collection(collect($page->items()))->resolve(),
            'page' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
            'filters' => ['q' => $q, 'status' => $status],
            'permissionsByModule' => Permission::query()
                ->where('status', 'active')
                ->orderBy('module')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'module'])
                ->groupBy('module')
                ->map(fn ($items) => $items->values())
                ->toArray(),
        ]);
    }

    public function store(RoleStoreRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            $permissionIds = $data['permission_ids'] ?? [];
            unset($data['permission_ids']);

            $role = Role::create($data + [
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            $role->permissions()->sync($permissionIds);
            app(AuditLogService::class)->created($request, 'Roles', 'role', $role->id, 'El usuario '.$request->user()?->name.' creó el rol '.$role->name.'.', ['permissions' => $permissionIds] + $data);
        });

        return back()->with('success', 'Rol creado correctamente.');
    }

    public function update(RoleUpdateRequest $request, Role $role)
    {
        DB::transaction(function () use ($request, $role) {
            $before = $role->toArray();
            $data = $request->validated();
            $permissionIds = $data['permission_ids'] ?? [];
            unset($data['permission_ids']);

            $role->update($data + [
                'updated_by' => $request->user()?->id,
            ]);

            $role->permissions()->sync($permissionIds);
            app(AuditLogService::class)->updated($request, 'Roles', 'role', $role->id, 'El usuario '.$request->user()?->name.' actualizó el rol '.$role->name.'.', $before, ['permissions' => $permissionIds] + $data);
        });

        return back()->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Role $role)
    {
        $before = $role->toArray();
        $role->delete();
        app(AuditLogService::class)->deleted(request(), 'Roles', 'role', $role->id, 'El usuario '.request()->user()?->name.' eliminó el rol '.$role->name.'.', $before);

        return back()->with('success', 'Rol eliminado correctamente.');
    }
}
