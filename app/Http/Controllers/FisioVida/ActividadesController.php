<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Requests\Actividades\ActivityStoreRequest;
use App\Http\Requests\Actividades\ActivityUpdateRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActividadesController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->string('q'));
        $status = trim((string) $request->string('status'));
        $priority = trim((string) $request->string('priority'));

        $query = Activity::query()
            ->with(['responsible:id,name', 'patient:id,nombres,apellido_paterno,apellido_materno'])
            ->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($priority !== '') {
            $query->where('priority', $priority);
        }

        $page = $query->paginate(10)->withQueryString();

        return Inertia::render('Actividades/Index', [
            'rows' => ActivityResource::collection(collect($page->items()))->resolve(),
            'page' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
            'filters' => ['q' => $q, 'status' => $status, 'priority' => $priority],
            'users' => User::query()->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(ActivityStoreRequest $request)
    {
        Activity::create($request->validated() + [
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Actividad creada correctamente.');
    }

    public function update(ActivityUpdateRequest $request, Activity $actividade)
    {
        $data = $request->validated();
        if (($data['status'] ?? null) === 'completed' && ! $actividade->completed_at) {
            $data['completed_at'] = now();
            $data['completed_by'] = $request->user()?->id;
        }

        $actividade->update($data + [
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Actividad actualizada correctamente.');
    }

    public function complete(Activity $actividade)
    {
        $actividade->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => request()->user()?->id,
        ]);

        return back()->with('success', 'Actividad completada correctamente.');
    }

    public function cancel(Activity $actividade)
    {
        $actividade->update([
            'status' => 'cancelled',
        ]);

        return back()->with('success', 'Actividad cancelada correctamente.');
    }

    public function destroy(Activity $actividade)
    {
        $actividade->delete();

        return back()->with('success', 'Actividad eliminada correctamente.');
    }
}
