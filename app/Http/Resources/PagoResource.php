<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_persona_id' => $this->patient_persona_id,
            'patient_name' => $this->patient_name ?? null,
            'concept' => $this->concept,
            'payment_method' => $this->payment_method,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'paid_at' => $this->paid_at,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_by_name' => $this->created_by_name ?? null,
            'cancelled_at' => $this->cancelled_at,
            'cancelled_by' => $this->cancelled_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
