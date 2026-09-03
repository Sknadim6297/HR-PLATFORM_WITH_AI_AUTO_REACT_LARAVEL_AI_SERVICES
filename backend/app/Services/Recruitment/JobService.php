<?php

namespace App\Services\Recruitment;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class JobService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Job::query()->with('creator:id,name,email');

        if ($user->isCandidate()) {
            $query->where('status', JobStatus::Published->value);
        } else {
            if ($status = Arr::get($filters, 'status')) {
                $query->where('status', $status);
            }

            if ($department = Arr::get($filters, 'department')) {
                $query->where('department', $department);
            }

            if ($employmentType = Arr::get($filters, 'employment_type')) {
                $query->where('employment_type', $employmentType);
            }

            if ($search = Arr::get($filters, 'search')) {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder->where('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhere('department', 'like', '%'.$search.'%');
                });
            }

            if ($from = Arr::get($filters, 'from')) {
                $query->whereDate('created_at', '>=', $from);
            }

            if ($to = Arr::get($filters, 'to')) {
                $query->whereDate('created_at', '<=', $to);
            }
        }

        return $query->latest()->paginate((int) Arr::get($filters, 'per_page', 15));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Job
    {
        $job = Job::query()->create([
            ...$data,
            'created_by' => $actor->id,
            'status' => JobStatus::Draft,
        ]);

        $this->auditLogger->log($actor, $job, 'job.created', null, [
            'title' => $job->title,
            'status' => $job->status->value,
        ]);

        return $job->load('creator:id,name,email');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Job $job, array $data): Job
    {
        $old = $job->only([
            'title', 'department', 'description', 'requirements', 'responsibilities',
            'employment_type', 'location', 'salary_min', 'salary_max',
            'experience_min', 'experience_max', 'closing_at',
        ]);

        $job->fill($data)->save();

        $this->auditLogger->log($actor, $job, 'job.updated', $old, $job->only(array_keys($old)));

        return $job->fresh(['creator:id,name,email']);
    }

    public function publish(User $actor, Job $job): Job
    {
        $oldStatus = $job->status->value;

        $job->forceFill([
            'status' => JobStatus::Published,
            'published_at' => $job->published_at ?? now(),
        ])->save();

        $this->auditLogger->log($actor, $job, 'job.published', [
            'status' => $oldStatus,
        ], [
            'status' => $job->status->value,
            'published_at' => $job->published_at?->toIso8601String(),
        ]);

        return $job->fresh(['creator:id,name,email']);
    }

    public function close(User $actor, Job $job): Job
    {
        $oldStatus = $job->status->value;

        $job->forceFill([
            'status' => JobStatus::Closed,
            'closing_at' => now(),
        ])->save();

        $this->auditLogger->log($actor, $job, 'job.closed', [
            'status' => $oldStatus,
        ], [
            'status' => $job->status->value,
            'closing_at' => $job->closing_at?->toIso8601String(),
        ]);

        return $job->fresh(['creator:id,name,email']);
    }

    public function delete(User $actor, Job $job): void
    {
        $this->auditLogger->log($actor, $job, 'job.deleted', [
            'title' => $job->title,
            'status' => $job->status->value,
        ]);

        $job->delete();
    }
}
