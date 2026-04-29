<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class ConfiguracionUpdateRequest extends FormRequest {

    public function authorize(): bool {
        return true;
    }

    protected function prepareForValidation(): void {
        $this->merge([
            'clinic_name' => trim((string) $this->input('clinic_name')),
            'clinic_phone' => $this->filled('clinic_phone') ? trim((string) $this->input('clinic_phone')) : null,
            'clinic_email' => $this->filled('clinic_email') ? mb_strtolower(trim((string) $this->input('clinic_email'))) : null,
            'clinic_address' => $this->filled('clinic_address') ? trim((string) $this->input('clinic_address')) : null,
            'clinic_logo' => $this->filled('clinic_logo') ? trim((string) $this->input('clinic_logo')) : null,
            'default_currency' => mb_strtoupper(trim((string) $this->input('default_currency', 'MXN'))),
            'dark_mode_enabled' => filter_var($this->input('dark_mode_enabled'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function rules(): array {
        return [
            'clinic_name' => ['required', 'string', 'max:190'],
            'clinic_logo' => ['nullable', 'string', 'max:255'],
            'clinic_logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'clinic_phone' => ['nullable', 'string', 'max:40'],
            'clinic_email' => ['nullable', 'email', 'max:190'],
            'clinic_address' => ['nullable', 'string', 'max:255'],

            'primary_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'primary_hover_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'primary_foreground_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'app_background_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'card_background_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'sidebar_background_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],

            'default_currency' => ['required', 'string', 'max:10'],
            'appointment_default_duration' => ['required', 'integer', 'min:1', 'max:480'],
            'dark_mode_enabled' => ['boolean'],
        ];
    }

    public function messages(): array {
        return [
            'clinic_name.required' => 'El nombre de la clínica es obligatorio.',
            'clinic_email.email' => 'El correo de la clínica no tiene un formato válido.',

            'clinic_logo_file.image' => 'El logo debe ser una imagen.',
            'clinic_logo_file.mimes' => 'El logo debe ser JPG, PNG, WEBP o SVG.',
            'clinic_logo_file.max' => 'El logo no debe pesar más de 4 MB.',
            'primary_hover_color.regex' => 'El color hover de botones debe ser HEX válido.',

            'primary_color.regex' => 'El color de botones debe ser HEX válido.',
            'primary_foreground_color.regex' => 'El color de texto de botones debe ser HEX válido.',
            'app_background_color.regex' => 'El color de fondo general debe ser HEX válido.',
            'card_background_color.regex' => 'El color de tarjetas debe ser HEX válido.',
            'sidebar_background_color.regex' => 'El color del menú lateral debe ser HEX válido.',

            'default_currency.max' => 'La moneda no puede tener más de 10 caracteres.',
            'appointment_default_duration.min' => 'La duración de cita debe ser mayor a 0.',
            'appointment_default_duration.max' => 'La duración de cita no puede superar 480 minutos.',
        ];
    }

}
