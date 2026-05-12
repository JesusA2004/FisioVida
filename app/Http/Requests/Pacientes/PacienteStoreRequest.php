<?php

namespace App\Http\Requests\Pacientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PacienteStoreRequest extends FormRequest {

    public function authorize(): bool {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $telefono = $this->input('telefono');
        $telefonoEmergencia = $this->input('contacto_emergencia_telefono');

        $this->merge([
            'status' => $this->input('status') ?: 'active',
            'sexo' => $this->input('sexo') ?: null,
            'telefono' => is_string($telefono)
                ? preg_replace('/\D+/', '', $telefono)
                : $telefono,
            'contacto_emergencia_telefono' => is_string($telefonoEmergencia)
                ? preg_replace('/\D+/', '', $telefonoEmergencia)
                : $telefonoEmergencia,
            'email' => $this->filled('email')
                ? mb_strtolower(trim((string) $this->input('email')))
                : null,
            'apellido_paterno' => $this->filled('apellido_paterno') ? trim((string) $this->input('apellido_paterno')) : null,
            'apellido_materno' => $this->filled('apellido_materno') ? trim((string) $this->input('apellido_materno')) : null,
            'direccion' => $this->filled('direccion') ? trim((string) $this->input('direccion')) : null,
            'contacto_emergencia_nombre' => $this->filled('contacto_emergencia_nombre') ? trim((string) $this->input('contacto_emergencia_nombre')) : null,
            'notas' => $this->filled('notas') ? trim((string) $this->input('notas')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'nombres' => ['required', 'string', 'max:120'],

            'apellido_paterno' => ['required', 'string', 'max:120'],
            'apellido_materno' => ['required', 'string', 'max:120'],

            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'sexo' => ['nullable', Rule::in(['M', 'F', 'X'])],

            'telefono' => ['required', 'digits:10'],
            'email' => ['nullable', 'email:rfc,dns', 'max:190'],

            'direccion' => ['nullable', 'string', 'max:255'],
            'contacto_emergencia_nombre' => ['nullable', 'string', 'max:190'],
            'contacto_emergencia_telefono' => ['nullable', 'digits:10'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombres.required' => 'El nombre del paciente es obligatorio.',
            'nombres.max' => 'El nombre no puede superar 120 caracteres.',

            'apellido_paterno.required' => 'El apellido paterno del paciente es obligatorio.',
            'apellido_paterno.max' => 'El apellido paterno no puede superar 120 caracteres.',

            'apellido_materno.required' => 'El apellido materno del paciente es obligatorio.',
            'apellido_materno.max' => 'El apellido materno no puede superar 120 caracteres.',

            'fecha_nacimiento.date' => 'La fecha de nacimiento no es válida.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',

            'telefono.required' => 'El teléfono del paciente es obligatorio.',
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
            'contacto_emergencia_telefono.digits' => 'El teléfono de emergencia debe tener exactamente 10 dígitos.',

            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.max' => 'El correo no puede superar 190 caracteres.',

            'direccion.max' => 'La dirección no puede superar 255 caracteres.',
            'contacto_emergencia_nombre.max' => 'El contacto de emergencia no puede superar 190 caracteres.',
            'notas.max' => 'Las notas no pueden superar 2000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombres' => 'nombres',
            'apellido_paterno' => 'apellido paterno',
            'apellido_materno' => 'apellido materno',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'telefono' => 'teléfono',
            'email' => 'correo electrónico',
            'direccion' => 'dirección',
            'contacto_emergencia_nombre' => 'contacto de emergencia',
            'contacto_emergencia_telefono' => 'teléfono de emergencia',
            'notas' => 'notas',
        ];
    }
}