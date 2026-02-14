<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Persona extends Model {

    use HasFactory, SoftDeletes;

    protected $table = 'personas';

    protected $fillable = [
        'tipo',
        'status',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'email',
        'direccion',
        'contacto_emergencia_nombre',
        'contacto_emergencia_telefono',
        'notas',
    ];

    protected function casts(): array {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    public function user() {
        return $this->hasOne(User::class, 'persona_id');
    }

    public function appointmentsAsPatient() {
        return $this->hasMany(Appointment::class, 'patient_persona_id');
    }

    public function sessionsAsPatient() {
        return $this->hasMany(TherapySession::class, 'patient_persona_id');
    }

    public function files() {
        return $this->hasMany(StoredFile::class, 'patient_persona_id');
    }

}
