<?php
// app/Http/Controllers/FisioVida/ArchivosController.php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ArchivosController extends Controller {

    use CrudHelpers;

    public function index(Request $request) {
        $q = $this->like($request->string('q'));
        $query = DB::table('files as f')
            ->leftJoin('personas as p', 'p.id','=','f.patient_persona_id')
            ->leftJoin('therapy_sessions as s', 's.id','=','f.session_id')
            ->leftJoin('users as u', 'u.id','=','f.uploaded_by')
            ->whereNull('p.deleted_at')
            ->select([
                'f.id','f.patient_persona_id','f.session_id','f.uploaded_by',
                'f.disk','f.path','f.original_name','f.mime','f.size_bytes','f.created_at',
                DB::raw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) as patient_name"),
                's.session_date',
                'u.name as uploaded_by_name',
            ])
            ->orderByDesc('f.id');
        if ($q) {
            $query->where(function($w) use ($q){
                $w->where('f.original_name','like',$q)
                    ->orWhere('p.nombres','like',$q)
                    ->orWhere('p.apellido_paterno','like',$q)
                    ->orWhere('u.name','like',$q);
            });
        }
        $p = $query->paginate(10)->withQueryString();
        $rows = collect($p->items())->map(function($r){
            $url = null;
            try { $url = Storage::disk($r->disk ?? 'public')->url($r->path); } catch (\Throwable $e) {}
            return [
                'id' => $r->id,
                'patient_persona_id' => $r->patient_persona_id,
                'patient_name' => $r->patient_name,
                'session_id' => $r->session_id,
                'session_date' => $r->session_date,
                'uploaded_by' => $r->uploaded_by,
                'uploaded_by_name' => $r->uploaded_by_name,
                'disk' => $r->disk,
                'path' => $r->path,
                'original_name' => $r->original_name,
                'mime' => $r->mime,
                'size_bytes' => $r->size_bytes,
                'created_at' => $r->created_at,
                'url' => $url,
            ];
        })->values();
        $patients = DB::table('personas')
            ->whereNull('deleted_at')->whereIn('tipo',['paciente','ambos'])->where('status','active')
            ->orderBy('apellido_paterno')->limit(200)
            ->get(['id', DB::raw("TRIM(CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno)) as label")]);
        $sessions = DB::table('therapy_sessions')
            ->orderByDesc('session_date')->limit(200)
            ->get(['id', DB::raw("CONCAT('#',id,' • ',DATE_FORMAT(session_date,'%Y-%m-%d')) as label")]);
        return Inertia::render('Archivos/Index', [
            'rows' => $rows,
            'page' => $this->packPaginator($p),
            'filters' => $this->filters($request, ['q']),
            'lookups' => ['patients' => $patients, 'sessions' => $sessions],
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'patient_persona_id' => ['nullable','integer'],
            'session_id' => ['nullable','integer'],
            'file' => ['required','file','max:10240'], // 10MB
        ]);
        $up = $request->file('file');
        $disk = 'public';
        $path = $up->store('fisio-vida', $disk);
        DB::table('files')->insert([
            'patient_persona_id' => $data['patient_persona_id'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'uploaded_by' => $request->user()?->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $up->getClientOriginalName(),
            'mime' => $up->getClientMimeType(),
            'size_bytes' => $up->getSize(),
            'created_at' => now(),
        ]);
        return back()->with('success', 'Archivo cargado.');
    }

    public function destroy(string $id) {
        $row = DB::table('files')->where('id',$id)->first();
        if ($row) {
            try { Storage::disk($row->disk ?? 'public')->delete($row->path); } catch (\Throwable $e) {}
            DB::table('files')->where('id',$id)->delete();
        }
        return back()->with('success', 'Archivo eliminado.');
    }

}
