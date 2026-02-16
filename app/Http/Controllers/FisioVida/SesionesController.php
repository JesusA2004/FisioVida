<?php
// app/Http/Controllers/FisioVida/SesionesController.php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SesionesController extends Controller {

    use CrudHelpers;

    public function index(Request $request) {
        $q = $this->like($request->string('q'));
        $query = DB::table('therapy_sessions as s')
            ->join('personas as p', 'p.id', '=', 's.patient_persona_id')
            ->join('users as u', 'u.id', '=', 's.therapist_user_id')
            ->whereNull('p.deleted_at')
            ->whereNull('u.deleted_at')
            ->select([
                's.id','s.appointment_id','s.patient_persona_id','s.therapist_user_id','s.session_date',
                's.subjective','s.objective','s.assessment','s.plan','s.pain_scale','s.notes',
                DB::raw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) as patient_name"),
                'u.name as therapist_name',
                's.created_at','s.updated_at',
            ])
            ->orderByDesc('s.session_date')
            ->orderByDesc('s.id');
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
            ->get(['id', DB::raw("TRIM(CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno)) as label")]);
        $therapists = DB::table('users')
            ->whereNull('deleted_at')->where('status','active')
            ->orderBy('name')->limit(200)
            ->get(['id', DB::raw("name as label")]);
        $appointments = DB::table('appointments as a')
            ->join('personas as p', 'p.id','=','a.patient_persona_id')
            ->whereNull('p.deleted_at')
            ->orderByDesc('a.start_at')
            ->limit(200)
            ->get([
                'a.id',
                DB::raw("CONCAT('#',a.id,' • ',TRIM(CONCAT_WS(' ',p.nombres,p.apellido_paterno,p.apellido_materno)),' • ',DATE_FORMAT(a.start_at,'%Y-%m-%d %H:%i')) as label")
            ]);
        return Inertia::render('Sesiones/Index', [
            'rows' => $rows,
            'page' => $this->packPaginator($p),
            'filters' => $this->filters($request, ['q']),
            'lookups' => [
                'patients' => $patients,
                'therapists' => $therapists,
                'appointments' => $appointments,
            ],
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'appointment_id' => ['nullable','integer'],
            'patient_persona_id' => ['required','integer'],
            'therapist_user_id' => ['required','integer'],
            'session_date' => ['required','date'],

            'subjective' => ['nullable','string'],
            'objective' => ['nullable','string'],
            'assessment' => ['nullable','string'],
            'plan' => ['nullable','string'],

            'pain_scale' => ['nullable','integer','min:0','max:10'],
            'notes' => ['nullable','string'],
        ]);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('therapy_sessions')->insert($data);
        return back()->with('success', 'Sesión creada.');
    }

    public function update(Request $request, string $id) {
        $data = $request->validate([
            'appointment_id' => ['nullable','integer'],
            'patient_persona_id' => ['required','integer'],
            'therapist_user_id' => ['required','integer'],
            'session_date' => ['required','date'],

            'subjective' => ['nullable','string'],
            'objective' => ['nullable','string'],
            'assessment' => ['nullable','string'],
            'plan' => ['nullable','string'],
            'pain_scale' => ['nullable','integer','min:0','max:10'],
            'notes' => ['nullable','string'],
        ]);
        $data['updated_at'] = now();
        DB::table('therapy_sessions')->where('id',$id)->update($data);
        return back()->with('success', 'Sesión actualizada.');
    }

    public function destroy(string $id) {
        DB::table('therapy_sessions')->where('id',$id)->delete();
        return back()->with('success', 'Sesión eliminada.');
    }

}
