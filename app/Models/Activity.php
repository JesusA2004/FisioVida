<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'responsible_user_id',
        'patient_persona_id',
        'appointment_id',
        'therapy_session_id',
        'payment_id',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'completed_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'patient_persona_id');
    }
}
