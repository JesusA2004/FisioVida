<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ModuleSettingsUpdateRequest;
use App\Models\ModuleSetting;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;

class ModulosSistemaController extends Controller
{
    public function update(ModuleSettingsUpdateRequest $request)
    {
        $before = ModuleSetting::query()->pluck('enabled', 'module')->toArray();
        DB::transaction(function () use ($request) {
            foreach ($request->validated('modules') as $module) {
                ModuleSetting::query()
                    ->where('module', $module['module'])
                    ->update(['enabled' => $module['enabled']]);
            }
        });
        app(AuditLogService::class)->updated(
            $request,
            'Configuración',
            'module_settings',
            null,
            'El usuario '.$request->user()?->name.' actualizó los módulos habilitados del sistema.',
            $before,
            $request->validated('modules'),
        );

        return back()->with('success', 'Módulos del sistema actualizados correctamente.');
    }
}
