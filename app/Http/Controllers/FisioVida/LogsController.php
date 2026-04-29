<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LogsController extends Controller
{
    use CrudHelpers;

    public function index(Request $request)
    {
        $q = $this->like($request->string('q'));
        $module = $request->string('module')->toString();
        $action = $request->string('action')->toString();
        $userId = (int) $request->integer('user_id');
        $start = $request->string('start_date')->toString();
        $end = $request->string('end_date')->toString();
        $perPageInput = $request->input('per_page', 15);

        $query = DB::table('logs as l')
            ->leftJoin('users as u', 'u.id', '=', DB::raw('COALESCE(l.user_id, l.actor_user_id)'))
            ->select([
                'l.id',
                DB::raw('COALESCE(l.user_id, l.actor_user_id) as user_id'),
                DB::raw('COALESCE(l.module, l.entity_type) as module'),
                'l.action',
                DB::raw('COALESCE(l.auditable_type, l.entity_type) as auditable_type'),
                DB::raw('COALESCE(l.auditable_id, l.entity_id) as auditable_id'),
                DB::raw('COALESCE(l.human_message, l.message) as human_message'),
                'l.old_values',
                'l.new_values',
                DB::raw('COALESCE(l.ip_address, l.ip) as ip_address'),
                'l.user_agent',
                'l.created_at',
                'u.name as user_name',
            ])
            ->orderByDesc('l.id');

        if ($module !== '') {
            $query->where(DB::raw('COALESCE(l.module, l.entity_type)'), $module);
        }

        if ($action !== '') {
            $query->where('l.action', $action);
        }

        if ($userId > 0) {
            $query->where(DB::raw('COALESCE(l.user_id, l.actor_user_id)'), $userId);
        }

        if ($start !== '') {
            $query->whereDate('l.created_at', '>=', $start);
        }

        if ($end !== '') {
            $query->whereDate('l.created_at', '<=', $end);
        }

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('l.human_message', 'like', $q)
                    ->orWhere('l.message', 'like', $q)
                    ->orWhere('l.action', 'like', $q)
                    ->orWhere('u.name', 'like', $q);
            });
        }

        if ($perPageInput === 'all') {
            $total = (clone $query)->count();
            $perPage = max(1, min($total, 500));
        } else {
            $perPage = (int) $perPageInput;
            $perPage = in_array($perPage, [10, 15, 20, 50], true)
                ? $perPage
                : 15;
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Logs/Index', [
            'rows' => collect($paginator->items())->values(),
            'page' => [
                ...$this->packPaginator($paginator),
                'per_page_selected' => $perPageInput === 'all' ? 'all' : $perPage,
            ],
            'filters' => $this->filters($request, [
                'q',
                'module',
                'action',
                'user_id',
                'start_date',
                'end_date',
                'per_page',
            ]),
            'lookups' => [
                'users' => DB::table('users')
                    ->whereNull('deleted_at')
                    ->orderBy('name')
                    ->limit(200)
                    ->get([
                        'id',
                        DB::raw('name as label'),
                    ]),

                'modules' => DB::table('logs')
                    ->select(DB::raw('COALESCE(module, entity_type) as value'))
                    ->whereNotNull(DB::raw('COALESCE(module, entity_type)'))
                    ->distinct()
                    ->orderBy('value')
                    ->pluck('value'),

                'actions' => DB::table('logs')
                    ->select('action')
                    ->whereNotNull('action')
                    ->distinct()
                    ->orderBy('action')
                    ->pluck('action'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        return back()->with('warning', 'La bitácora se registra automáticamente.');
    }

    public function destroy(string $id)
    {
        DB::table('logs')->where('id', $id)->delete();

        return back()->with('success', 'Registro eliminado.');
    }
}
