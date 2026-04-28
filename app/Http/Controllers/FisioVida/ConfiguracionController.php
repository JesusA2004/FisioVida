<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfiguracionUpdateRequest;
use App\Http\Resources\ModuleSettingResource;
use App\Http\Resources\SystemSettingResource;
use App\Models\ModuleSetting;
use App\Models\SystemSetting;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::query()->orderBy('group')->orderBy('key')->get();

        return Inertia::render('Configuracion/Index', [
            'settings' => SystemSettingResource::collection($settings)->resolve(),
            'settingsMap' => $settings->pluck('value', 'key'),
            'modules' => ModuleSettingResource::collection(ModuleSetting::query()->orderBy('sort_order')->get())->resolve(),
        ]);
    }

    public function update(ConfiguracionUpdateRequest $request)
    {
        $before = SystemSetting::query()->pluck('value', 'key')->toArray();
        DB::transaction(function () use ($request) {
            foreach ($request->validated() as $key => $value) {
                SystemSetting::query()->where('key', $key)->update([
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                ]);
            }
        });
        app(AuditLogService::class)->updated(
            $request,
            'Configuración',
            'system_settings',
            null,
            'El usuario '.$request->user()?->name.' actualizó la configuración general de la clínica.',
            $before,
            $request->validated(),
        );

        return back()->with('success', 'Configuración general actualizada correctamente.');
    }
}
