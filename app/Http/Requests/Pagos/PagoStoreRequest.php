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
            'provider' => ['required', 'string', 'max:50'],
            'provider_payment_id' => ['nullable', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', Rule::in(['pending', 'paid', 'failed', 'refunded'])],
            'paid_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
