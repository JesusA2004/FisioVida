<?php

namespace App\Http\Requests\Citas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CitaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_persona_id' => ['required', 'integer', 'exists:personas,id'],
            'therapist_user_id' => ['required', 'integer', 'exists:users,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'status' => ['required', Rule::in(['scheduled', 'confirmed', 'arrived', 'no_show', 'cancelled', 'done'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
