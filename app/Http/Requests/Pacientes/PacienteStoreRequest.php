<?php

namespace App\Http\Requests\Pacientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PacienteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'nombres' => ['required', 'string', 'max:120'],
            'apellido_paterno' => ['nullable', 'string', 'max:120'],
            'apellido_materno' => ['nullable', 'string', 'max:120'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'sexo' => ['nullable', Rule::in(['M', 'F', 'X'])],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'contacto_emergencia_nombre' => ['nullable', 'string', 'max:190'],
            'contacto_emergencia_telefono' => ['nullable', 'string', 'max:30'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
