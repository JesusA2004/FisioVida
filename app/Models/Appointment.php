<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model {

    use HasFactory;

    protected $fillable = [
        'patient_persona_id',
        'therapist_user_id',
        'start_at',
        'end_at',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function patient() {
        return $this->belongsTo(Persona::class, 'patient_persona_id');
    }

    public function therapist() {
        return $this->belongsTo(User::class, 'therapist_user_id');
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessions() {
        return $this->hasMany(TherapySession::class, 'appointment_id');
    }

}
