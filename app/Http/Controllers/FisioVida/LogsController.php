<?php
// app/Http/Controllers/FisioVida/LogsController.php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LogsController extends Controller {

    use CrudHelpers;

    public function index(Request $request) {
        $q = $this->like($request->string('q'));
        $level = $request->string('level')->toString();
        $query = DB::table('logs as l')
            ->leftJoin('users as u', 'u.id','=','l.actor_user_id')
            ->select([
                'l.id','l.level','l.actor_user_id','u.name as actor_name',
                'l.action','l.entity_type','l.entity_id','l.message','l.ip','l.user_agent','l.created_at'
            ])
            ->orderByDesc('l.id');
        if ($level !== '') $query->where('l.level', $level);
        if ($q) {
            $query->where(function($w) use ($q){
                $w->where('l.action','like',$q)
                    ->orWhere('l.message','like',$q)
                    ->orWhere('l.entity_type','like',$q)
                    ->orWhere('u.name','like',$q);
            });
        }
        $p = $query->paginate(15)->withQueryString();
        $rows = collect($p->items())->values();
        $actors = DB::table('users')->whereNull('deleted_at')->orderBy('name')->limit(200)
            ->get(['id', DB::raw("name as label")]);
        return Inertia::render('Logs/Index', [
            'rows' => $rows,
            'page' => $this->packPaginator($p),
            'filters' => $this->filters($request, ['q','level']),
            'lookups' => ['actors' => $actors],
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'level' => ['required','in:info,warning,error,audit'],
            'actor_user_id' => ['nullable','integer'],
            'action' => ['required','string','max:120'],
            'entity_type' => ['nullable','string','max:80'],
            'entity_id' => ['nullable','integer'],
            'message' => ['required','string','max:255'],
        ]);
        $data['ip'] = $request->ip();
        $data['user_agent'] = substr((string)$request->userAgent(), 0, 255);
        $data['created_at'] = now();
        DB::table('logs')->insert($data);
        return back()->with('success', 'Log creado.');
    }

    public function destroy(string $id) {
        DB::table('logs')->where('id',$id)->delete();
        return back()->with('success', 'Log eliminado.');
    }

}
