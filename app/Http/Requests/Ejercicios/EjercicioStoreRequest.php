<?php

namespace App\Http\Requests\Ejercicios;

use Illuminate\Foundation\Http\FormRequest;

class EjercicioStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
