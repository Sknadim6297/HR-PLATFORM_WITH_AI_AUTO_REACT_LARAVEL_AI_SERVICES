<?php

namespace App\Services\Recruitment;

use App\Enums\ApplicationStatus;
use App\Events\ApplicationShortlisted;
use App\Events\CandidateRejected;
use App\Events\CandidateSelected;
use App\Events\InterviewScheduled;
use App\Exceptions\InvalidApplicationTransitionException;
use App\Exceptions\OwnedResourceNotFoundException;
use App\Models\JobApplication;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class ApplicationStatusService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function transition(User $actor, JobApplication $application, ApplicationStatus $next): JobApplication
    {
        $current = $application->status;

        if ($actor->isCandidate()) {
            if ($application->candidate_id !== $actor->id) {
                throw new OwnedResourceNotFoundException('Application not found.');
            }

            if ($next !== ApplicationStatus::Withdrawn) {
                throw new OwnedResourceNotFoundException('Application not found.');
            }

            if (! $current->canTransitionTo(ApplicationStatus::Withdrawn)) {
                throw new InvalidApplicationTransitionException(
                    'This application can no longer be withdrawn.'
                );
            }
        } elseif (! $actor->isAdmin() && ! $actor->isHr()) {
            throw new OwnedResourceNotFoundException('Application not found.');
        }

        if (! $current->canTransitionTo($next)) {
            throw new InvalidApplicationTransitionException(
                "Cannot transition from [{$current->value}] to [{$next->value}]."
            );
        }

        $updated = DB::transaction(function () use ($actor, $application, $current, $next): JobApplication {
            $application->forceFill([
                'status' => $next,
            ])->save();

            $this->auditLogger->log($actor, $application, 'application.status_changed', [
                'status' => $current->value,
            ], [
                'status' => $next->value,
            ], [
                'job_id' => $application->job_id,
                'candidate_id' => $application->candidate_id,
            ]);

            return $application->fresh([
                'job.creator:id,name,email',
                'candidate:id,name,email',
                'resumeAnalysis',
                'jobMatch',
            ]) ?? $application;
        });

        $this->dispatchDomainEvents($updated, $next);

        return $updated;
    }

    private function dispatchDomainEvents(JobApplication $application, ApplicationStatus $status): void
    {
        match ($status) {
            ApplicationStatus::Shortlisted => ApplicationShortlisted::dispatch($application),
            ApplicationStatus::Interview => InterviewScheduled::dispatch($application),
            ApplicationStatus::Selected => CandidateSelected::dispatch($application),
            ApplicationStatus::Rejected => CandidateRejected::dispatch($application),
            default => null,
        };
    }
}
