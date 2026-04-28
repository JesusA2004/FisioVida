<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class ConfiguracionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinic_name' => ['required', 'string', 'max:190'],
            'clinic_logo' => ['nullable', 'string', 'max:255'],
            'clinic_phone' => ['nullable', 'string', 'max:40'],
            'clinic_email' => ['nullable', 'email', 'max:190'],
            'clinic_address' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'secondary_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'accent_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'default_currency' => ['required', 'string', 'max:10'],
            'appointment_default_duration' => ['required', 'integer', 'min:1'],
            'demo_mode' => ['boolean'],
            'dark_mode_enabled' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'clinic_name.required' => 'El nombre de la clínica es obligatorio.',
            'clinic_email.email' => 'El correo de la clínica no tiene un formato válido.',
            'primary_color.regex' => 'El color primario debe ser un valor HEX válido (ej. #3B82F6).',
            'secondary_color.regex' => 'El color secundario debe ser un valor HEX válido (ej. #22C55E).',
            'accent_color.regex' => 'El color de acento debe ser un valor HEX válido (ej. #F59E0B).',
            'default_currency.max' => 'La moneda no puede tener más de 10 caracteres.',
            'appointment_default_duration.min' => 'La duración de cita debe ser mayor a 0.',
        ];
    }
}
