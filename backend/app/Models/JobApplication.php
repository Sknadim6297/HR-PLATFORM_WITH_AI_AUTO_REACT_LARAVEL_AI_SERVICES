<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property ApplicationStatus $status
 */
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    protected $table = 'job_applications';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'job_id',
        'candidate_id',
        'resume_document_id',
        'cover_letter',
        'status',
        'applied_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'applied',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'applied_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function resumeDocument(): BelongsTo
    {
        return $this->belongsTo(AiDocument::class, 'resume_document_id');
    }

    public function resumeAnalysis(): HasOne
    {
        return $this->hasOne(AiResumeAnalysis::class, 'application_id');
    }

    public function jobMatch(): HasOne
    {
        return $this->hasOne(AiJobMatch::class, 'application_id');
    }
}
