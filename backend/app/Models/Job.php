<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property JobStatus $status
 * @property EmploymentType $employment_type
 */
class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasFactory;

    protected $table = 'job_postings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'created_by',
        'title',
        'slug',
        'department',
        'description',
        'requirements',
        'responsibilities',
        'employment_type',
        'location',
        'salary_min',
        'salary_max',
        'experience_min',
        'experience_max',
        'status',
        'published_at',
        'closing_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'status' => JobStatus::class,
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'experience_min' => 'integer',
            'experience_max' => 'integer',
            'published_at' => 'datetime',
            'closing_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Job $job): void {
            if (! filled($job->slug)) {
                $job->slug = static::uniqueSlug((string) $job->title);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_id');
    }

    public function isPublished(): bool
    {
        return $this->status === JobStatus::Published;
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug(Str::limit($title, 180, '')) ?: 'job';
        $slug = $base;
        $counter = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
