<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SessionExercise extends Pivot {

    protected $table = 'session_exercises';

    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'session_id',
        'exercise_id',
        'sets',
        'reps',
        'seconds',
        'notes',
    ];

}
