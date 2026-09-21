<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiScreeningAssessment extends Model
{
    protected $fillable = [
        'application_id',
        'recommendation',
        'score',
        'reasoning',
        'confidence',
        'model',
        'screened_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'screened_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }
}
