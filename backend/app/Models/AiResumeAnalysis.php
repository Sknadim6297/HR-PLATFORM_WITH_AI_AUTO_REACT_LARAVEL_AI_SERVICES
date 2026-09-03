<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiResumeAnalysis extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'summary',
        'skills',
        'experience',
        'education',
        'strengths',
        'gaps',
        'confidence',
        'model',
        'metadata',
        'analyzed_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'experience' => 'array',
            'education' => 'array',
            'strengths' => 'array',
            'gaps' => 'array',
            'metadata' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    public function isComplete(): bool
    {
        return $this->analyzed_at !== null && filled($this->summary);
    }
}
