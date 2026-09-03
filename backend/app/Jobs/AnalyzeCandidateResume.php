<?php

namespace App\Jobs;

use App\Enums\AiDocumentStatus;
use App\Exceptions\LlmProviderException;
use App\Models\JobApplication;
use App\Services\AI\ResumeAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyzeCandidateResume implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @var list<int>
     */
    public array $backoff = [30, 60, 120];

    public function __construct(public int $applicationId) {}

    public function handle(ResumeAnalysisService $service): void
    {
        $application = JobApplication::query()
            ->with(['resumeDocument', 'resumeAnalysis'])
            ->find($this->applicationId);

        if ($application === null) {
            Log::warning('Resume analysis skipped; application missing.', [
                'application_id' => $this->applicationId,
            ]);

            return;
        }

        if ($application->resumeAnalysis?->isComplete()) {
            return;
        }

        if ($application->resumeDocument?->status !== AiDocumentStatus::Completed) {
            Log::info('Resume analysis deferred; document not completed.', [
                'application_id' => $application->id,
                'document_id' => $application->resume_document_id,
            ]);

            return;
        }

        try {
            $service->analyze($application);
        } catch (LlmProviderException $exception) {
            if ($exception->isRetryable()) {
                throw $exception;
            }

            Log::error('Resume analysis failed permanently.', [
                'application_id' => $application->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('AnalyzeCandidateResume job failed.', [
            'application_id' => $this->applicationId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
