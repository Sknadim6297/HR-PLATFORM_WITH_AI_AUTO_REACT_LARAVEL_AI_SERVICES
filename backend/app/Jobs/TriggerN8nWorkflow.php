<?php

namespace App\Jobs;

use App\Enums\AutomationWorkflow;
use App\Exceptions\AutomationException;
use App\Services\Automation\N8nService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class TriggerN8nWorkflow implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [15, 45, 90];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $workflow,
        public string $eventKey,
        public array $payload = [],
    ) {}

    public function handle(N8nService $n8nService): void
    {
        $workflow = AutomationWorkflow::tryFrom($this->workflow);

        if ($workflow === null) {
            Log::warning('Unknown automation workflow skipped.', [
                'workflow' => $this->workflow,
                'event_key' => $this->eventKey,
            ]);

            return;
        }

        try {
            $n8nService->dispatch($workflow, $this->eventKey, $this->payload);
        } catch (AutomationException $exception) {
            if ($exception->isRetryable()) {
                throw $exception;
            }

            Log::error('Automation permanently failed.', [
                'workflow' => $this->workflow,
                'event_key' => $this->eventKey,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('TriggerN8nWorkflow failed.', [
            'workflow' => $this->workflow,
            'event_key' => $this->eventKey,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
