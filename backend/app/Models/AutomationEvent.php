<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'workflow',
        'event_key',
        'status',
        'payload',
        'attempts',
        'last_error',
        'dispatched_at',
        'completed_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'attempts' => 0,
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
