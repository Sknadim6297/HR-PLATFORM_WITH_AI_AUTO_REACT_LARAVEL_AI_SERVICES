<?php

namespace App\Services\Recruitment;

use App\Enums\AiDocumentStatus;
use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Events\ApplicationSubmitted;
use App\Exceptions\OwnedResourceNotFoundException;
use App\Jobs\AnalyzeCandidateResume;
use App\Models\AiDocument;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ApplicationService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function apply(User $candidate, Job $job, array $data): JobApplication
    {
        if ($job->status !== JobStatus::Published) {
            throw new OwnedResourceNotFoundException('Job not found.');
        }

        if (JobApplication::query()
            ->where('job_id', $job->id)
            ->where('candidate_id', $candidate->id)
            ->exists()) {
            throw new ConflictHttpException('You have already applied to this job.');
        }

        $resumeDocumentId = Arr::get($data, 'resume_document_id');

        if ($resumeDocumentId !== null) {
            $document = AiDocument::query()
                ->whereKey($resumeDocumentId)
                ->where('user_id', $candidate->id)
                ->first();

            if ($document === null) {
                throw new OwnedResourceNotFoundException('Document not found.');
            }
        }

        $application = DB::transaction(function () use ($candidate, $job, $data, $resumeDocumentId): JobApplication {
            return JobApplication::query()->create([
                'job_id' => $job->id,
                'candidate_id' => $candidate->id,
                'resume_document_id' => $resumeDocumentId,
                'cover_letter' => Arr::get($data, 'cover_letter'),
                'status' => ApplicationStatus::Applied,
                'applied_at' => now(),
            ]);
        });

        $this->auditLogger->log($candidate, $application, 'application.submitted', null, [
            'job_id' => $job->id,
            'status' => $application->status->value,
            'resume_document_id' => $resumeDocumentId,
        ]);

        ApplicationSubmitted::dispatch($application);

        if ($resumeDocumentId !== null) {
            $document = AiDocument::query()->find($resumeDocumentId);

            if ($document?->status === AiDocumentStatus::Completed) {
                AnalyzeCandidateResume::dispatch($application->id);
            }
        }

        return $application->load(['job.creator:id,name,email', 'resumeDocument']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = JobApplication::query()
            ->with([
                'job:id,title,slug,status,department,employment_type,created_by',
                'candidate:id,name,email',
                'jobMatch:id,application_id,score,confidence',
                'resumeAnalysis:id,application_id,confidence,analyzed_at',
            ]);

        if ($user->isCandidate()) {
            $query->where('candidate_id', $user->id);
        }

        if ($status = Arr::get($filters, 'status')) {
            $query->where('status', $status);
        }

        if ($jobId = Arr::get($filters, 'job_id')) {
            $query->where('job_id', $jobId);
        }

        if ($candidateId = Arr::get($filters, 'candidate_id')) {
            if ($user->isAdmin() || $user->isHr()) {
                $query->where('candidate_id', $candidateId);
            }
        }

        if (($minScore = Arr::get($filters, 'min_score')) !== null) {
            $query->whereHas('jobMatch', fn (Builder $q) => $q->where('score', '>=', (int) $minScore));
        }

        if (($maxScore = Arr::get($filters, 'max_score')) !== null) {
            $query->whereHas('jobMatch', fn (Builder $q) => $q->where('score', '<=', (int) $maxScore));
        }

        if ($from = Arr::get($filters, 'from')) {
            $query->whereDate('applied_at', '>=', $from);
        }

        if ($to = Arr::get($filters, 'to')) {
            $query->whereDate('applied_at', '<=', $to);
        }

        if ($search = Arr::get($filters, 'search')) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('cover_letter', 'like', '%'.$search.'%')
                    ->orWhereHas('candidate', fn (Builder $q) => $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%'))
                    ->orWhereHas('job', fn (Builder $q) => $q->where('title', 'like', '%'.$search.'%'));
            });
        }

        return $query->latest('applied_at')->paginate((int) Arr::get($filters, 'per_page', 15));
    }

    public function listForJob(User $user, Job $job, array $filters = []): LengthAwarePaginator
    {
        $filters['job_id'] = $job->id;

        return $this->listForUser($user, $filters);
    }

    public function findAccessible(User $user, int $applicationId): JobApplication
    {
        $query = JobApplication::query()
            ->with([
                'job.creator:id,name,email',
                'candidate:id,name,email',
                'candidate.candidateProfile',
                'resumeDocument',
                'resumeAnalysis',
                'jobMatch',
            ])
            ->whereKey($applicationId);

        if ($user->isCandidate()) {
            $query->where('candidate_id', $user->id);
        }

        $application = $query->first();

        if ($application === null) {
            throw new OwnedResourceNotFoundException('Application not found.');
        }

        return $application;
    }
}
