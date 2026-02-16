<?php
// app/Http/Controllers/FisioVida/CitasController.php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CitasController extends Controller {

    use CrudHelpers;

    public function index(Request $request) {
        $q = $this->like($request->string('q'));
        $status = $request->string('status')->toString();
        $query = DB::table('appointments as a')
            ->join('personas as p', 'p.id', '=', 'a.patient_persona_id')
            ->join('users as u', 'u.id', '=', 'a.therapist_user_id')
            ->whereNull('p.deleted_at')
            ->whereNull('u.deleted_at')
            ->select([
                'a.id','a.patient_persona_id','a.therapist_user_id','a.start_at','a.end_at','a.status','a.notes','a.created_by',
                DB::raw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) as patient_name"),
                'u.name as therapist_name',
                'a.created_at','a.updated_at',
            ])
            ->orderByDesc('a.start_at');
        if ($status !== '') $query->where('a.status', $status);
        if ($q) {
            $query->where(function($w) use ($q){
                $w->where('p.nombres','like',$q)
                    ->orWhere('p.apellido_paterno','like',$q)
                    ->orWhere('p.apellido_materno','like',$q)
                    ->orWhere('u.name','like',$q);
            });
        }
        $p = $query->paginate(10)->withQueryString();
        $rows = collect($p->items())->values();
        $patients = DB::table('personas')
            ->whereNull('deleted_at')->whereIn('tipo',['paciente','ambos'])->where('status','active')
            ->orderBy('apellido_paterno')->limit(200)
            ->get([
                'id',
                DB::raw("TRIM(CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno)) as label")
            ]);
        $therapists = DB::table('users')
            ->whereNull('deleted_at')->where('status','active')
            ->orderBy('name')->limit(200)
            ->get(['id', DB::raw("name as label")]);
        return Inertia::render('Agenda/Index', [
            'rows' => $rows,
            'page' => $this->packPaginator($p),
            'filters' => $this->filters($request, ['q','status']),
            'lookups' => [
                'patients' => $patients,
                'therapists' => $therapists,
            ],
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'patient_persona_id' => ['required','integer'],
            'therapist_user_id' => ['required','integer'],
            'start_at' => ['required','date'],
            'end_at' => ['required','date','after:start_at'],
            'status' => ['required','in:scheduled,confirmed,arrived,no_show,cancelled,done'],
            'notes' => ['nullable','string','max:255'],
        ]);
        $data['created_by'] = $request->user()?->id;
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('appointments')->insert($data);
        return back()->with('success', 'Cita creada.');
    }

    public function update(Request $request, string $id) {
        $data = $request->validate([
            'patient_persona_id' => ['required','integer'],
            'therapist_user_id' => ['required','integer'],
            'start_at' => ['required','date'],
            'end_at' => ['required','date','after:start_at'],
            'status' => ['required','in:scheduled,confirmed,arrived,no_show,cancelled,done'],
            'notes' => ['nullable','string','max:255'],
        ]);
        $data['updated_at'] = now();
        DB::table('appointments')->where('id',$id)->update($data);
        return back()->with('success', 'Cita actualizada.');
    }

    public function destroy(string $id) {
        DB::table('appointments')->where('id',$id)->delete();
        return back()->with('success', 'Cita eliminada.');
    }

}
