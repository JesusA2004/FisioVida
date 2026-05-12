<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchivosController extends Controller
{
    use CrudHelpers;

    private array $fileTypes = [
        'estudio_clinico' => 'Estudio clínico',
        'consentimiento' => 'Consentimiento',
        'evidencia' => 'Evidencia',
        'receta_indicacion' => 'Receta / indicación',
        'documento_administrativo' => 'Documento administrativo',
        'imagen_clinica' => 'Imagen clínica',
        'laboratorio' => 'Laboratorio',
        'otro' => 'Otro',
    ];

    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $perPageInput = $request->input('per_page', 10);

        $query = DB::table('files as f')
            ->leftJoin('personas as p', 'p.id', '=', 'f.patient_persona_id')
            ->leftJoin('therapy_sessions as s', 's.id', '=', 'f.session_id')
            ->leftJoin('personas as sp', 'sp.id', '=', 's.patient_persona_id')
            ->leftJoin('users as u', 'u.id', '=', 'f.uploaded_by')
            ->where(function ($where) {
                $where->whereNull('p.id')->orWhereNull('p.deleted_at');
            })
            ->where(function ($where) {
                $where->whereNull('sp.id')->orWhereNull('sp.deleted_at');
            })
            ->select([
                'f.id',
                'f.patient_persona_id',
                'f.session_id',
                'f.uploaded_by',
                'f.disk',
                'f.path',
                'f.original_name',
                'f.file_type',
                'f.mime',
                'f.size_bytes',
                'f.created_at',
                DB::raw("
                    COALESCE(
                        NULLIF(TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)), ''),
                        NULLIF(TRIM(CONCAT_WS(' ', sp.nombres, sp.apellido_paterno, sp.apellido_materno)), '')
                    ) as patient_name
                "),
                's.session_date',
                'u.name as uploaded_by_name',
            ])
            ->orderByDesc('f.id');

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';

            $query->where(function ($where) use ($like) {
                $where->where('f.original_name', 'like', $like)
                    ->orWhere('f.file_type', 'like', $like)
                    ->orWhere('f.mime', 'like', $like)
                    ->orWhere('p.nombres', 'like', $like)
                    ->orWhere('p.apellido_paterno', 'like', $like)
                    ->orWhere('p.apellido_materno', 'like', $like)
                    ->orWhere('sp.nombres', 'like', $like)
                    ->orWhere('sp.apellido_paterno', 'like', $like)
                    ->orWhere('sp.apellido_materno', 'like', $like)
                    ->orWhere('u.name', 'like', $like);
            });
        }

        if ($request->filled('file_type')) {
            $query->where('f.file_type', $request->input('file_type'));
        }

        if ($request->filled('patient_persona_id')) {
            $query->where('f.patient_persona_id', (int) $request->input('patient_persona_id'));
        }

        if ($request->filled('session_id')) {
            $query->where('f.session_id', (int) $request->input('session_id'));
        }

        $perPage = $perPageInput === 'all'
            ? max(1, min((clone $query)->count(), 500))
            : (int) $perPageInput;

        $perPage = in_array($perPage, [10, 15, 20, 50], true)
            ? $perPage
            : 10;

        if ($perPageInput === 'all') {
            $total = (clone $query)->count();
            $perPage = max(1, min($total, 500));
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Archivos/Index', [
            'rows' => $this->mapRows(collect($paginator->items())),
            'page' => [
                ...$this->packPaginator($paginator),
                'per_page_selected' => $perPageInput === 'all' ? 'all' : $perPage,
            ],
            'filters' => [
                'q' => $q,
                'file_type' => $request->filled('file_type') ? $request->input('file_type') : '',
                'patient_persona_id' => $request->filled('patient_persona_id') ? $request->input('patient_persona_id') : '',
                'session_id' => $request->filled('session_id') ? $request->input('session_id') : '',
                'per_page' => $perPageInput,
            ],
            'lookups' => [
                'patients' => $this->patientsLookup(),
                'sessions' => $this->sessionsLookup(),
                'fileTypes' => $this->fileTypeOptions(),
            ],
        ]);
    }

    public function show(Request $request, string $archivo)
    {
        $row = DB::table('files')->where('id', $archivo)->first();

        abort_if(! $row, 404);

        $disk = $row->disk ?? 'public';

        abort_if(! Storage::disk($disk)->exists($row->path), 404);

        app(AuditLogService::class)->fileViewed($request, (int) $archivo, (string) ($row->original_name ?? 'archivo'));

        return response()->file(
            Storage::disk($disk)->path($row->path),
            [
                'Content-Type' => $row->mime ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . addslashes($row->original_name ?? 'archivo') . '"',
            ],
        );
    }

    public function download(Request $request, string $archivo): StreamedResponse
    {
        $row = DB::table('files')->where('id', $archivo)->first();

        abort_if(! $row, 404);

        $disk = $row->disk ?? 'public';

        abort_if(! Storage::disk($disk)->exists($row->path), 404);

        app(AuditLogService::class)->fileDownloaded($request, (int) $archivo, (string) ($row->original_name ?? 'archivo'));

        return Storage::disk($disk)->download(
            $row->path,
            $row->original_name ?: basename($row->path),
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_persona_id' => ['nullable', 'integer', 'exists:personas,id', 'prohibits:session_id'],
            'session_id' => ['nullable', 'integer', 'exists:therapy_sessions,id', 'prohibits:patient_persona_id'],
            'file_type' => ['required', 'string', Rule::in(array_keys($this->fileTypes))],
            'files' => ['required', 'array', 'min:1', 'max:15'],
            'files.*' => [
                'required',
                'file',
                'max:51200',
                'mimes:pdf,jpg,jpeg,png,webp,gif,svg,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip',
            ],
        ], $this->validationMessages());

        $disk = 'private';
        $storedIds = [];
        $storedNames = [];
        $storedPaths = [];

        DB::beginTransaction();

        try {
            foreach ($request->file('files', []) as $uploadedFile) {
                $directory = 'fisio-vida/archivos/' . now()->format('Y/m');

                $safeOriginalName = Str::of($uploadedFile->getClientOriginalName())
                    ->replaceMatches('/[^\pL\pN\.\-\_\s]/u', '')
                    ->trim()
                    ->toString();

                $path = $uploadedFile->store($directory, $disk);

                $storedPaths[] = compact('disk', 'path');

                $id = DB::table('files')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'patient_persona_id' => $data['patient_persona_id'] ?? null,
                    'session_id' => $data['session_id'] ?? null,
                    'uploaded_by' => $request->user()?->id,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $safeOriginalName !== '' ? $safeOriginalName : $uploadedFile->getClientOriginalName(),
                    'file_type' => $data['file_type'],
                    'mime' => $uploadedFile->getClientMimeType(),
                    'size_bytes' => $uploadedFile->getSize(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $storedIds[] = $id;
                $storedNames[] = $uploadedFile->getClientOriginalName();
            }

            app(AuditLogService::class)->created(
                $request,
                'Archivos',
                'file',
                implode(',', $storedIds),
                'El usuario ' . $request->user()?->name . ' subió ' . count($storedIds) . ' archivo(s).',
                [
                    'ids' => $storedIds,
                    'files' => $storedNames,
                    'patient_persona_id' => $data['patient_persona_id'] ?? null,
                    'session_id' => $data['session_id'] ?? null,
                    'file_type' => $data['file_type'],
                ],
            );

            DB::commit();

            return back()->with('success', count($storedIds) === 1
                ? 'Archivo cargado correctamente.'
                : 'Archivos cargados correctamente.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            foreach ($storedPaths as $storedPath) {
                try {
                    Storage::disk($storedPath['disk'])->delete($storedPath['path']);
                } catch (\Throwable) {
                    //
                }
            }

            report($e);

            return back()
                ->withErrors([
                    'files' => 'No se pudieron subir los archivos. Intenta nuevamente.',
                ])
                ->withInput();
        }
    }

    public function update(Request $request, string $id)
    {
        $row = DB::table('files')->where('id', $id)->first();

        abort_if(! $row, 404);

        $data = $request->validate([
            'patient_persona_id' => ['nullable', 'integer', 'exists:personas,id', 'prohibits:session_id'],
            'session_id' => ['nullable', 'integer', 'exists:therapy_sessions,id', 'prohibits:patient_persona_id'],
            'file_type' => ['required', 'string', Rule::in(array_keys($this->fileTypes))],
            'replacement_file' => [
                'nullable',
                'file',
                'max:51200',
                'mimes:pdf,jpg,jpeg,png,webp,gif,svg,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip',
            ],
        ], $this->validationMessages());

        $oldValues = (array) $row;
        $payload = [
            'patient_persona_id' => $data['patient_persona_id'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'file_type' => $data['file_type'],
        ];

        $newPath = null;
        $oldPath = $row->path;
        $oldDisk = $row->disk ?? 'public';
        $disk = 'private';

        DB::beginTransaction();

        try {
            if ($request->hasFile('replacement_file')) {
                $uploadedFile = $request->file('replacement_file');
                $directory = 'fisio-vida/archivos/' . now()->format('Y/m');

                $safeOriginalName = Str::of($uploadedFile->getClientOriginalName())
                    ->replaceMatches('/[^\pL\pN\.\-\_\s]/u', '')
                    ->trim()
                    ->toString();

                $newPath = $uploadedFile->store($directory, $disk);

                $payload = [
                    ...$payload,
                    'disk' => $disk,
                    'path' => $newPath,
                    'original_name' => $safeOriginalName !== '' ? $safeOriginalName : $uploadedFile->getClientOriginalName(),
                    'mime' => $uploadedFile->getClientMimeType(),
                    'size_bytes' => $uploadedFile->getSize(),
                ];
            }

            DB::table('files')->where('id', $id)->update($payload);

            if ($newPath && $oldPath) {
                try {
                    Storage::disk($oldDisk)->delete($oldPath);
                } catch (\Throwable) {
                    //
                }
            }

            app(AuditLogService::class)->updated(
                $request,
                'Archivos',
                'file',
                (int) $id,
                'El usuario ' . $request->user()?->name . ' actualizó el archivo "' . ($row->original_name ?? 'Sin nombre') . '".',
                $oldValues,
                $payload,
            );

            DB::commit();

            return back()->with('success', 'Archivo actualizado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($newPath) {
                try {
                    Storage::disk($disk)->delete($newPath);
                } catch (\Throwable) {
                    //
                }
            }

            report($e);

            return back()->withErrors([
                'archivo' => 'No se pudo actualizar el archivo. Intenta nuevamente.',
            ]);
        }
    }

    public function destroy(Request $request, string $id)
    {
        $row = DB::table('files')->where('id', $id)->first();

        if (! $row) {
            return back()->with('success', 'El archivo ya no existe.');
        }

        DB::beginTransaction();

        try {
            try {
                Storage::disk($row->disk ?? 'public')->delete($row->path);
            } catch (\Throwable) {
                //
            }

            DB::table('files')->where('id', $id)->delete();

            app(AuditLogService::class)->deleted(
                $request,
                'Archivos',
                'file',
                (int) $id,
                'El usuario ' . $request->user()?->name . ' eliminó el archivo "' . ($row->original_name ?? 'Sin nombre') . '".',
                (array) $row,
            );

            DB::commit();

            return back()->with('success', 'Archivo eliminado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            return back()->withErrors([
                'archivo' => 'No se pudo eliminar el archivo. Intenta nuevamente.',
            ]);
        }
    }

    private function mapRows($items)
    {
        return collect($items)->map(function ($row) {
            $extension = strtolower(pathinfo((string) $row->original_name, PATHINFO_EXTENSION));
            $mime = (string) ($row->mime ?? '');

            $isImage = str_starts_with($mime, 'image/');
            $isPdf = $mime === 'application/pdf' || $extension === 'pdf';

            return [
                'id' => $row->id,
                'patient_persona_id' => $row->patient_persona_id,
                'patient_name' => $row->patient_name,
                'session_id' => $row->session_id,
                'session_date' => $row->session_date,
                'uploaded_by' => $row->uploaded_by,
                'uploaded_by_name' => $row->uploaded_by_name,
                'disk' => $row->disk,
                'path' => $row->path,
                'original_name' => $row->original_name,
                'file_type' => $row->file_type,
                'file_type_label' => $this->fileTypes[$row->file_type] ?? 'Sin clasificar',
                'mime' => $row->mime,
                'extension' => $extension,
                'size_bytes' => $row->size_bytes,
                'created_at' => $row->created_at,
                'url' => "/archivos/{$row->id}",
                'preview_url' => "/archivos/{$row->id}",
                'download_url' => "/archivos/{$row->id}/descargar",
                'is_image' => $isImage,
                'is_pdf' => $isPdf,
                'can_preview' => $isImage || $isPdf,
            ];
        })->values();
    }

    private function patientsLookup()
    {
        return DB::table('personas')
            ->whereNull('deleted_at')
            ->whereIn('tipo', ['paciente', 'ambos'])
            ->where('status', 'active')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->limit(500)
            ->get([
                'id',
                DB::raw("TRIM(CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno)) as label"),
            ]);
    }

    private function sessionsLookup()
    {
        return DB::table('therapy_sessions as s')
            ->leftJoin('personas as p', 'p.id', '=', 's.patient_persona_id')
            ->where(function ($where) {
                $where->whereNull('p.id')->orWhereNull('p.deleted_at');
            })
            ->orderByDesc('s.session_date')
            ->orderByDesc('s.id')
            ->limit(500)
            ->get([
                's.id',
                's.patient_persona_id',
                DB::raw("
                    CONCAT(
                        '#',
                        s.id,
                        ' • ',
                        DATE_FORMAT(s.session_date, '%Y-%m-%d'),
                        CASE
                            WHEN p.id IS NOT NULL THEN CONCAT(' • ', TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)))
                            ELSE ''
                        END
                    ) as label
                "),
            ]);
    }

    private function fileTypeOptions(): array
    {
        return collect($this->fileTypes)
            ->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    private function validationMessages(): array
    {
        return [
            'patient_persona_id.prohibits' => 'Selecciona un paciente o una sesión, no ambos.',
            'session_id.prohibits' => 'Selecciona una sesión o un paciente, no ambos.',
            'file_type.required' => 'Selecciona el tipo de archivo.',
            'file_type.in' => 'El tipo de archivo seleccionado no es válido.',
            'files.required' => 'Selecciona al menos un archivo.',
            'files.array' => 'El formato de archivos no es válido.',
            'files.min' => 'Selecciona al menos un archivo.',
            'files.max' => 'Solo puedes subir hasta 15 archivos a la vez.',
            'files.*.required' => 'Uno de los archivos no se recibió correctamente.',
            'files.*.file' => 'Uno de los elementos seleccionados no es un archivo válido.',
            'files.*.max' => 'Cada archivo debe pesar máximo 50 MB.',
            'files.*.mimes' => 'Solo se permiten PDF, imágenes, Word, Excel, PowerPoint, TXT, CSV y ZIP.',
            'replacement_file.file' => 'El archivo de reemplazo no es válido.',
            'replacement_file.max' => 'El archivo de reemplazo debe pesar máximo 50 MB.',
            'replacement_file.mimes' => 'Solo se permiten PDF, imágenes, Word, Excel, PowerPoint, TXT, CSV y ZIP.',
        ];
    }
}
