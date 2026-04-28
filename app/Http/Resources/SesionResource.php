<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SesionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'patient_persona_id' => $this->patient_persona_id,
            'therapist_user_id' => $this->therapist_user_id,
            'session_date' => $this->session_date,
            'subjective' => $this->subjective,
            'objective' => $this->objective,
            'assessment' => $this->assessment,
            'plan' => $this->plan,
            'pain_scale' => $this->pain_scale,
            'notes' => $this->notes,
            'patient_name' => $this->patient_name,
            'therapist_name' => $this->therapist_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
