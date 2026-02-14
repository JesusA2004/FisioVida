<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable {

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        // relación persona
        'persona_id',
        // auth base
        'name',
        'email',
        'password',
        // negocio
        'status',
        'is_super_admin',
        // módulos (permisos por usuario)
        'mod_agenda',
        'mod_pacientes',
        'mod_sesiones',
        'mod_ejercicios',
        'mod_archivos',
        'mod_reportes',
        'mod_cobranza',
        'mod_config',
        // auditoría ligera
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        // Fortify 2FA
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',

            // Fortify 2FA
            'two_factor_confirmed_at' => 'datetime',

            // flags
            'is_super_admin' => 'boolean',
            'mod_agenda' => 'boolean',
            'mod_pacientes' => 'boolean',
            'mod_sesiones' => 'boolean',
            'mod_ejercicios' => 'boolean',
            'mod_archivos' => 'boolean',
            'mod_reportes' => 'boolean',
            'mod_cobranza' => 'boolean',
            'mod_config' => 'boolean',

            // audit
            'last_login_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones FisioVida
    |--------------------------------------------------------------------------
    */

    public function persona() {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function appointmentsAsTherapist() {
        return $this->hasMany(Appointment::class, 'therapist_user_id');
    }

    public function appointmentsCreated() {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    public function sessionsAsTherapist() {
        return $this->hasMany(TherapySession::class, 'therapist_user_id');
    }

    public function uploadedFiles() {
        return $this->hasMany(StoredFile::class, 'uploaded_by');
    }

    public function logEntries() {
        return $this->hasMany(LogEntry::class, 'actor_user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers de permisos (prácticos)
    |--------------------------------------------------------------------------
    */

    public function isSuperAdmin(): bool {
        return (bool) $this->is_super_admin;
    }

    public function canModule(string $module): bool {
        if ($this->isSuperAdmin()) return true;
        return match ($module) {
            'agenda' => (bool) $this->mod_agenda,
            'pacientes' => (bool) $this->mod_pacientes,
            'sesiones' => (bool) $this->mod_sesiones,
            'ejercicios' => (bool) $this->mod_ejercicios,
            'archivos' => (bool) $this->mod_archivos,
            'reportes' => (bool) $this->mod_reportes,
            'cobranza' => (bool) $this->mod_cobranza,
            'config' => (bool) $this->mod_config,
            default => false,
        };
    }

}
