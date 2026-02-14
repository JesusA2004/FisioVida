<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogEntry extends Model {

    use HasFactory;

    protected $table = 'logs';

    public $timestamps = false;
    const UPDATED_AT = null;

    protected $fillable = [
        'level',
        'actor_user_id',
        'action',
        'entity_type',
        'entity_id',
        'message',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array {
        return [
            'entity_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function actor() {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

}
