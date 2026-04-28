<?php

namespace App\Http\Requests\Sesiones;

use Illuminate\Foundation\Http\FormRequest;

class SesionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'patient_persona_id' => ['required', 'integer', 'exists:personas,id'],
            'therapist_user_id' => ['required', 'integer', 'exists:users,id'],
            'session_date' => ['required', 'date'],
            'subjective' => ['nullable', 'string'],
            'objective' => ['nullable', 'string'],
            'assessment' => ['nullable', 'string'],
            'plan' => ['nullable', 'string'],
            'pain_scale' => ['nullable', 'integer', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
