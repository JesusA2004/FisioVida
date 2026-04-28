<?php

namespace App\Http\Requests\Configuracion;

use App\Models\ModuleSetting;
use Illuminate\Foundation\Http\FormRequest;

class ModuleSettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $modules = ModuleSetting::query()->pluck('module')->all();

        return [
            'modules' => ['required', 'array'],
            'modules.*.module' => ['required', 'string', 'in:' . implode(',', $modules)],
            'modules.*.enabled' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'modules.required' => 'Debes enviar la configuración de módulos.',
            'modules.*.module.in' => 'Se detectó un módulo no válido en la configuración.',
        ];
    }
}
