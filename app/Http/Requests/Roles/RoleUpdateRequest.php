<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', 'alpha_dash', Rule::unique('roles', 'slug')->ignore($this->route('role'))],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del rol es obligatorio.',
            'slug.required' => 'El slug del rol es obligatorio.',
            'slug.unique' => 'Ya existe un rol con este slug.',
            'slug.alpha_dash' => 'El slug solo puede contener letras, números, guiones y guion bajo.',
            'status.required' => 'El estado del rol es obligatorio.',
        ];
    }
}
