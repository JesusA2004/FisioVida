<?php

namespace App\Http\Requests\Permisos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:140'],
            'slug' => ['required', 'string', 'max:160', 'alpha_dash', Rule::unique('permissions', 'slug')],
            'module' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del permiso es obligatorio.',
            'slug.required' => 'El slug del permiso es obligatorio.',
            'slug.unique' => 'Este slug ya está en uso.',
            'module.required' => 'El módulo del permiso es obligatorio.',
            'status.required' => 'El estado del permiso es obligatorio.',
        ];
    }
}
