<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\RoleStoreRequest;
use App\Http\Requests\Roles\RoleUpdateRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RolesController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->string('q'));
        $status = trim((string) $request->string('status'));

        $query = Role::query()->orderByDesc('id');

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
        ]);
    }

    public function store(RoleStoreRequest $request)
    {
        Role::create($request->validated() + [
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Rol creado correctamente.');
    }

    public function update(RoleUpdateRequest $request, Role $role)
    {
        $role->update($request->validated() + [
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return back()->with('success', 'Rol eliminado correctamente.');
    }
}
