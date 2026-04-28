<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'responsible_user_id' => $this->responsible_user_id,
            'responsible_name' => $this->responsible?->name,
            'patient_persona_id' => $this->patient_persona_id,
            'patient_name' => $this->patient
                ? trim(implode(' ', array_filter([$this->patient->nombres, $this->patient->apellido_paterno, $this->patient->apellido_materno])))
                : null,
            'priority' => $this->priority,
            'status' => $this->status,
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_overdue' => $this->due_date && in_array($this->status, ['pending', 'in_progress', 'on_hold'], true)
                ? now()->greaterThan($this->due_date)
                : false,
        ];
    }
}
