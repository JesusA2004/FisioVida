<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model {

    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'video_url',
        'is_active',
    ];

    protected function casts(): array {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function sessions() {
        return $this->belongsToMany(TherapySession::class, 'session_exercises', 'exercise_id', 'session_id')
            ->withPivot(['sets', 'reps', 'seconds', 'notes']);
    }

}
