<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiJobMatch extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'score',
        'matched_skills',
        'missing_skills',
        'reasoning',
        'confidence',
        'model',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'matched_skills' => 'array',
            'missing_skills' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }
}
