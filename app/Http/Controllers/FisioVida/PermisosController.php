<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permisos\PermissionStoreRequest;
use App\Http\Requests\Permisos\PermissionUpdateRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PermisosController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->string('q'));
        $status = trim((string) $request->string('status'));
        $module = trim((string) $request->string('module'));

        $query = Permission::query()->orderBy('module')->orderBy('slug');

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

        if ($module !== '') {
            $query->where('module', $module);
        }

        $page = $query->paginate(12)->withQueryString();

        return Inertia::render('Permisos/Index', [
            'rows' => PermissionResource::collection(collect($page->items()))->resolve(),
            'page' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
            'filters' => ['q' => $q, 'status' => $status, 'module' => $module],
            'moduleOptions' => Permission::query()->select('module')->distinct()->orderBy('module')->pluck('module')->values(),
        ]);
    }

    public function store(PermissionStoreRequest $request)
    {
        $permission = Permission::create($request->validated());
        app(AuditLogService::class)->created($request, 'Permisos', 'permission', $permission->id, 'El usuario '.$request->user()?->name.' creó el permiso '.$permission->name.'.', $permission->toArray());

        return back()->with('success', 'Permiso creado correctamente.');
    }

    public function update(PermissionUpdateRequest $request, Permission $permiso)
    {
        $before = $permiso->toArray();
        $permiso->update($request->validated());
        app(AuditLogService::class)->updated($request, 'Permisos', 'permission', $permiso->id, 'El usuario '.$request->user()?->name.' actualizó el permiso '.$permiso->name.'.', $before, $request->validated());

        return back()->with('success', 'Permiso actualizado correctamente.');
    }

    public function toggleStatus(Permission $permiso)
    {
        $from = $permiso->status;
        $permiso->update([
            'status' => $permiso->status === 'active' ? 'inactive' : 'active',
        ]);
        app(AuditLogService::class)->statusChanged(request(), 'Permisos', 'permission', $permiso->id, 'El usuario '.request()->user()?->name.' cambió el estado del permiso '.$permiso->name.'.', $from, $permiso->status);

        return back()->with('success', 'Estado del permiso actualizado correctamente.');
    }

    public function destroy(Permission $permiso)
    {
        $before = $permiso->toArray();
        $permiso->delete();
        app(AuditLogService::class)->deleted(request(), 'Permisos', 'permission', $permiso->id, 'El usuario '.request()->user()?->name.' eliminó el permiso '.$permiso->name.'.', $before);

        return back()->with('success', 'Permiso eliminado correctamente.');
    }
}
