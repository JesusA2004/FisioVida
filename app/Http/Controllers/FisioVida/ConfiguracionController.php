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
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ConfiguracionController extends Controller {

    public function index() {
        $settings = SystemSetting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        return Inertia::render('Configuracion/Index', [
            'settings' => SystemSettingResource::collection($settings)->resolve(),
            'settingsMap' => $settings->pluck('value', 'key'),
            'modules' => ModuleSettingResource::collection(
                ModuleSetting::query()->orderBy('sort_order')->get()
            )->resolve(),
        ]);
    }

    public function update(ConfiguracionUpdateRequest $request) {
        $before = SystemSetting::query()->pluck('value', 'key')->toArray();
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated) {
            $payload = collect($validated)
                ->except('clinic_logo_file')
                ->toArray();

            if ($request->hasFile('clinic_logo_file')) {
                $oldLogo = SystemSetting::query()
                    ->where('key', 'clinic_logo')
                    ->value('value');

                $path = $request->file('clinic_logo_file')->store('logos', 'public');

                $payload['clinic_logo'] = Storage::url($path);

                if (
                    $oldLogo &&
                    str_starts_with($oldLogo, '/storage/logos/') &&
                    Storage::disk('public')->exists(str_replace('/storage/', '', $oldLogo))
                ) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $oldLogo));
                }
            }

            foreach ($payload as $key => $value) {
                SystemSetting::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                        'group' => $this->groupForKey($key),
                        'label' => $this->labelForKey($key),
                        'type' => $this->typeForKey($key),
                        'is_public' => $this->isPublicKey($key),
                    ]
                );
            }
        });

        app(AuditLogService::class)->updated(
            $request,
            'Configuración',
            'system_settings',
            null,
            'El usuario '.$request->user()?->name.' actualizó la configuración general de la clínica.',
            $before,
            $validated,
        );

        return back()->with('success', 'Configuración general actualizada correctamente.');
    }

    private function groupForKey(string $key): string {
        return match ($key) {
            'clinic_name',
            'clinic_logo',
            'clinic_phone',
            'clinic_email',
            'clinic_address' => 'clinic',
            'primary_color',
            'primary_hover_color',
            'primary_foreground_color',
            'app_background_color',
            'card_background_color',
            'sidebar_background_color' => 'appearance',
            default => 'general',
        };
    }

    private function typeForKey(string $key): string {
        return match ($key) {
            'dark_mode_enabled' => 'boolean',
            'appointment_default_duration' => 'number',
            default => 'string',
        };
    }

    private function labelForKey(string $key): string {
        return match ($key) {
            'clinic_name' => 'Nombre de clínica',
            'clinic_logo' => 'Logo',
            'clinic_phone' => 'Teléfono',
            'clinic_email' => 'Email',
            'clinic_address' => 'Dirección',
            'primary_color' => 'Color de botones',
            'primary_hover_color' => 'Hover de botones',
            'primary_foreground_color' => 'Texto de botones',
            'app_background_color' => 'Fondo general',
            'card_background_color' => 'Fondo de tarjetas',
            'sidebar_background_color' => 'Fondo del menú lateral',
            'default_currency' => 'Moneda',
            'appointment_default_duration' => 'Duración de cita',
            'dark_mode_enabled' => 'Modo oscuro habilitado',
            default => str($key)->replace('_', ' ')->title()->toString(),
        };
    }

    private function isPublicKey(string $key): bool {
        return in_array($key, [
            'clinic_name',
            'clinic_logo',
            'primary_color',
            'primary_hover_color',
            'primary_foreground_color',
            'app_background_color',
            'card_background_color',
            'sidebar_background_color',
        ], true);
    }

}
