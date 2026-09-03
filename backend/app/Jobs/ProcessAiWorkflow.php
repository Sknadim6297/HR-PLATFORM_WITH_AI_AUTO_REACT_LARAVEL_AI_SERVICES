<?php

namespace App\Jobs;

use App\Enums\AiWorkflowStatus;
use App\Exceptions\AiProviderException;
use App\Models\AiWorkflow;
use App\Services\AI\AIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAiWorkflow implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 75;

    public bool $failOnTimeout = true;

    /**
     * @var list<int>
     */
    public array $backoff = [15, 45];

    public function __construct(
        public int $workflowId,
    ) {}

    public function handle(AIService $aiService): void
    {
        $workflow = AiWorkflow::query()->find($this->workflowId);

        if ($workflow === null) {
            Log::warning('AI workflow job skipped because the record was not found.', [
                'workflow_id' => $this->workflowId,
            ]);

            return;
        }

        if ($workflow->status === AiWorkflowStatus::Completed) {
            return;
        }

        $workflow->forceFill([
            'status' => AiWorkflowStatus::Processing,
            'error_message' => null,
        ])->save();

        Log::info('AI workflow started.', [
            'workflow_id' => $workflow->id,
            'user_id' => $workflow->user_id,
            'task' => $workflow->task->value,
        ]);

        $output = $aiService->runWorkflow($workflow->task, $workflow->content);

        $workflow->forceFill([
            'status' => AiWorkflowStatus::Completed,
            'result' => $output['result'],
            'error_message' => null,
        ])->save();

        Log::info('AI workflow completed.', [
            'workflow_id' => $workflow->id,
            'user_id' => $workflow->user_id,
            'task' => $workflow->task->value,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $workflow = AiWorkflow::query()->find($this->workflowId);

        if ($workflow === null) {
            return;
        }

        Log::error('AI workflow failed.', [
            'workflow_id' => $workflow->id,
            'user_id' => $workflow->user_id,
            'task' => $workflow->task->value,
            'exception' => $exception?->getMessage(),
        ]);

        $workflow->forceFill([
            'status' => AiWorkflowStatus::Failed,
            'error_message' => $this->safeErrorMessage($exception),
        ])->save();
    }

    private function safeErrorMessage(?Throwable $exception): string
    {
        if ($exception instanceof AiProviderException) {
            return $exception->getMessage();
        }

        return 'The AI workflow failed. Please try again later.';
    }
}
