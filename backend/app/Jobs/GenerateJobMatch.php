<?php

namespace App\Jobs;

use App\Exceptions\LlmProviderException;
use App\Models\JobApplication;
use App\Services\AI\JobMatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateJobMatch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @var list<int>
     */
    public array $backoff = [30, 60, 120];

    public function __construct(public int $applicationId) {}

    public function handle(JobMatchingService $service): void
    {
        $application = JobApplication::query()
            ->with(['jobMatch', 'resumeAnalysis'])
            ->find($this->applicationId);

        if ($application === null) {
            return;
        }

        if ($application->jobMatch?->generated_at !== null) {
            return;
        }

        if ($application->resumeAnalysis === null || ! $application->resumeAnalysis->isComplete()) {
            Log::info('Job match deferred; resume analysis incomplete.', [
                'application_id' => $application->id,
            ]);

            return;
        }

        try {
            $service->match($application);
        } catch (LlmProviderException $exception) {
            if ($exception->isRetryable()) {
                throw $exception;
            }

            Log::error('Job matching failed permanently.', [
                'application_id' => $application->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('GenerateJobMatch job failed.', [
            'application_id' => $this->applicationId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
