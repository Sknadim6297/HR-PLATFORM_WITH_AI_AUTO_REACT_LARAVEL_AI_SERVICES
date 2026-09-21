<?php

namespace App\Jobs;

use App\Enums\ApplicationStatus;
use App\Enums\ScreeningRecommendation;
use App\Exceptions\LlmProviderException;
use App\Models\JobApplication;
use App\Services\AI\AiScreeningService;
use App\Services\Audit\AuditLogger;
use App\Services\Recruitment\ApplicationStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutoScreenApplication implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [30, 60, 120];

    public function __construct(public int $applicationId) {}

    public function handle(
        AiScreeningService $screeningService,
        ApplicationStatusService $statusService,
        AuditLogger $auditLogger,
    ): void {
        $application = JobApplication::query()
            ->with(['aiScreeningAssessment', 'resumeAnalysis', 'jobMatch'])
            ->find($this->applicationId);

        if ($application === null || $application->aiScreeningAssessment?->screened_at !== null) {
            return;
        }

        try {
            $assessment = $screeningService->screenAndPersist($application);
        } catch (LlmProviderException $exception) {
            if ($exception->isRetryable()) {
                throw $exception;
            }

            Log::error('Automatic AI screening failed permanently.', [
                'application_id' => $this->applicationId,
                'message' => $exception->getMessage(),
            ]);

            if ($this->job !== null) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        }

        $auditLogger->log(null, $application, 'application.ai_screened', null, [
            'recommendation' => $assessment->recommendation,
            'score' => $assessment->score,
            'confidence' => $assessment->confidence,
            'model' => $assessment->model,
        ]);

        $nextStatus = match (true) {
            $assessment->recommendation === ScreeningRecommendation::Reject->value,
            $assessment->score < 40 => ApplicationStatus::Rejected,
            $assessment->recommendation === ScreeningRecommendation::Interview->value => ApplicationStatus::Interview,
            $assessment->recommendation === ScreeningRecommendation::Shortlist->value,
            $assessment->score >= (int) config('automation.high_match_score', 80) => ApplicationStatus::Shortlisted,
            default => ApplicationStatus::Screening,
        };

        $application->refresh();

        if ($application->status !== $nextStatus) {
            $statusService->transitionAutomatically($application, $nextStatus, [
                'recommendation' => $assessment->recommendation,
                'score' => $assessment->score,
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Automatic AI screening job failed.', [
            'application_id' => $this->applicationId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
