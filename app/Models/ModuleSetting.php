<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'label',
        'description',
        'enabled',
        'sort_order',
        'settings',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'settings' => 'array',
    ];

    public static function enabledMap(): array
    {
        return static::query()->pluck('enabled', 'module')->toArray();
    }
}
