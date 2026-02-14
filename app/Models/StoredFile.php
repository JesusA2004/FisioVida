<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoredFile extends Model {

    use HasFactory;

    protected $table = 'files';

    const UPDATED_AT = null;

    protected $fillable = [
        'patient_persona_id',
        'session_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime',
        'size_bytes',
        'created_at',
    ];

    protected function casts(): array {
        return [
            'size_bytes' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function patient() {
        return $this->belongsTo(Persona::class, 'patient_persona_id');
    }

    public function session() {
        return $this->belongsTo(TherapySession::class, 'session_id');
    }

    public function uploader() {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

}
