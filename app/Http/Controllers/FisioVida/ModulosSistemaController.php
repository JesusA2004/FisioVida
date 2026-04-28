<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ModuleSettingsUpdateRequest;
use App\Models\ModuleSetting;
use Illuminate\Support\Facades\DB;

class ModulosSistemaController extends Controller
{
    public function update(ModuleSettingsUpdateRequest $request)
    {
        DB::transaction(function () use ($request) {
            foreach ($request->validated('modules') as $module) {
                ModuleSetting::query()
                    ->where('module', $module['module'])
                    ->update(['enabled' => $module['enabled']]);
            }
        });

        return back()->with('success', 'Módulos del sistema actualizados correctamente.');
    }
}
