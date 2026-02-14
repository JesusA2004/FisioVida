<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TherapySession extends Model {

    use HasFactory;

    protected $table = 'therapy_sessions';

    protected $fillable = [
        'appointment_id',
        'patient_persona_id',
        'therapist_user_id',
        'session_date',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'pain_scale',
        'notes',
    ];

    protected function casts(): array {
        return [
            'session_date' => 'date',
            'pain_scale' => 'integer',
        ];
    }

    public function appointment() {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function patient() {
        return $this->belongsTo(Persona::class, 'patient_persona_id');
    }

    public function therapist() {
        return $this->belongsTo(User::class, 'therapist_user_id');
    }

    public function exercises() {
        return $this->belongsToMany(Exercise::class, 'session_exercises', 'session_id', 'exercise_id')
            ->withPivot(['sets', 'reps', 'seconds', 'notes']);
    }

}
