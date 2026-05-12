<?php

namespace App\Http\Requests\Pagos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PagoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_persona_id' => ['nullable', 'integer', 'exists:personas,id'],
            'concept' => ['nullable', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_method' => ['nullable', 'string', Rule::in(['efectivo', 'transferencia', 'tarjeta_terminal', 'deposito', 'otro'])],
            'status' => ['required', Rule::in(['pending', 'paid', 'cancelled'])],
            'paid_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'El monto es obligatorio.',
            'amount.min' => 'El monto debe ser mayor a cero.',
            'currency.required' => 'La moneda es obligatoria.',
            'currency.size' => 'La moneda debe ser un código de 3 letras (MXN, USD, EUR).',
            'status.required' => 'El estado es obligatorio.',
            'payment_method.in' => 'El método de pago seleccionado no es válido.',
        ];
    }
}
