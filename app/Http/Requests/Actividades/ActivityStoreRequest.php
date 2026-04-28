<?php

namespace App\Http\Requests\Actividades;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'patient_persona_id' => ['nullable', 'integer', 'exists:personas,id'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'status' => ['required', Rule::in(['pending', 'in_progress', 'on_hold', 'completed', 'cancelled', 'overdue'])],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título de la actividad es obligatorio.',
            'priority.required' => 'La prioridad es obligatoria.',
            'status.required' => 'El estado es obligatorio.',
        ];
    }
}
