<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_persona_id' => $this->patient_persona_id,
            'therapist_user_id' => $this->therapist_user_id,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'patient_name' => $this->patient_name,
            'therapist_name' => $this->therapist_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
