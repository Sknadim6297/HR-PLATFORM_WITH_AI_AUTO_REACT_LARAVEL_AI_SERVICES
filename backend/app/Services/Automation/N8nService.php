<?php

namespace App\Services\Automation;

use App\Enums\AutomationWorkflow;
use App\Exceptions\AutomationException;
use App\Models\AutomationEvent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class N8nService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(AutomationWorkflow $workflow, string $eventKey, array $payload = []): AutomationEvent
    {
        $existing = AutomationEvent::query()->where('event_key', $eventKey)->first();

        if ($existing !== null) {
            if (in_array($existing->status, ['completed', 'processing', 'pending'], true)) {
                return $existing;
            }
        }

        $event = $existing ?? AutomationEvent::query()->create([
            'workflow' => $workflow->value,
            'event_key' => $eventKey,
            'status' => 'pending',
            'payload' => $payload,
        ]);

        if (! config('automation.enabled')) {
            $event->forceFill([
                'status' => 'skipped',
                'completed_at' => now(),
            ])->save();

            return $event;
        }

        $path = config('automation.workflows.'.$workflow->value);

        if (! is_string($path) || $path === '') {
            throw new AutomationException(
                "Workflow [{$workflow->value}] is not configured.",
                retryable: false,
            );
        }

        $baseUrl = (string) config('automation.base_url');

        if ($baseUrl === '') {
            throw new AutomationException(
                'n8n base URL is not configured.',
                retryable: false,
            );
        }

        $event->forceFill([
            'status' => 'processing',
            'attempts' => $event->attempts + 1,
            'dispatched_at' => now(),
            'payload' => $payload,
        ])->save();

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('automation.timeout', 15))
                ->post($baseUrl.'/'.ltrim($path, '/'), [
                    'workflow' => $workflow->value,
                    'event_key' => $eventKey,
                    'payload' => $payload,
                ]);
        } catch (ConnectionException $exception) {
            $this->markFailed($event, $exception->getMessage());

            throw new AutomationException(
                'Automation provider is temporarily unavailable.',
                retryable: true,
                previous: $exception,
            );
        } catch (Throwable $exception) {
            $this->markFailed($event, 'Automation request failed.');

            throw new AutomationException(
                'Automation provider is temporarily unavailable.',
                retryable: true,
                previous: $exception,
            );
        }

        if ($response->failed()) {
            $status = $response->status();
            $this->markFailed($event, 'HTTP '.$status);

            throw new AutomationException(
                'Automation provider is temporarily unavailable.',
                retryable: $status === 429 || $status >= 500,
            );
        }

        $event->forceFill([
            'status' => 'completed',
            'last_error' => null,
            'completed_at' => now(),
        ])->save();

        Log::info('Automation workflow dispatched.', [
            'workflow' => $workflow->value,
            'event_key' => $eventKey,
            'automation_event_id' => $event->id,
        ]);

        return $event;
    }

    private function markFailed(AutomationEvent $event, string $error): void
    {
        $event->forceFill([
            'status' => 'failed',
            'last_error' => $error,
        ])->save();
    }
}
