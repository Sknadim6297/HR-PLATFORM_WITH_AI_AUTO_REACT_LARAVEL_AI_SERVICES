<?php

namespace App\Services\AI;

use App\Enums\AiTask;
use App\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AIService
{
    public function __construct(
        private readonly AiPromptBuilder $promptBuilder,
    ) {}

    public function generate(string $prompt): array
    {
        $response = $this->complete([
            [
                'role' => 'system',
                'content' => 'You are a helpful AI assistant for an organizational management system.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ]);

        return [
            'success' => true,
            'provider' => 'openai',
            'response' => $response,
        ];
    }

    /**
     * @return array{success: true, task: string, result: array<string, mixed>}
     */
    public function runWorkflow(AiTask $task, string $content): array
    {
        $raw = $this->complete(
            $this->promptBuilder->messages($task, $content),
            [
                'response_format' => ['type' => 'json_object'],
                'temperature' => $this->temperatureFor($task),
            ],
        );

        $payload = $this->decodeJsonPayload($raw);

        return [
            'success' => true,
            'task' => $task->value,
            'result' => $this->normalizeResult($task, $payload),
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     */
    private function complete(array $messages, array $options = []): string
    {
        try {
            $response = Http::withToken(config('services.openai.key'))
                ->acceptJson()
                ->timeout(30)
                ->connectTimeout(10)
                ->post('https://api.openai.com/v1/chat/completions', array_merge([
                    'model' => config('services.openai.model'),
                    'messages' => $messages,
                ], $options));
        } catch (ConnectionException $exception) {
            $this->logFailure('AI provider connection failed.', $exception);

            throw new AiProviderException(
                'The AI service is temporarily unavailable.',
                504,
                $exception,
            );
        }

        if ($response->failed()) {
            $this->logFailure('AI provider request failed.', null, [
                'status' => $response->status(),
                'error' => $response->json('error.message'),
            ]);

            throw new AiProviderException(
                'The AI service is temporarily unavailable.',
                $response->status() === 429 ? 429 : 502,
            );
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            $this->logFailure('AI provider returned an empty response.');

            throw new AiProviderException(
                'The AI service returned an invalid response.',
                502,
            );
        }

        return $content;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonPayload(string $raw): array
    {
        $trimmed = trim($raw);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $trimmed, $matches) === 1) {
            $trimmed = trim($matches[1]);
        }

        try {
            $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->logFailure('AI provider returned malformed JSON.', $exception, [
                'snippet' => mb_substr($trimmed, 0, 500),
            ]);

            throw new AiProviderException(
                'The AI service returned an invalid response.',
                502,
                $exception,
            );
        }

        if (! is_array($decoded)) {
            $this->logFailure('AI provider JSON was not an object.', null, [
                'snippet' => mb_substr($trimmed, 0, 500),
            ]);

            throw new AiProviderException(
                'The AI service returned an invalid response.',
                502,
            );
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeResult(AiTask $task, array $payload): array
    {
        try {
            return match ($task) {
                AiTask::Summarize => [
                    'summary' => $this->requiredString($payload, 'summary'),
                ],
                AiTask::Generate => [
                    'content' => $this->requiredString($payload, 'content'),
                ],
                AiTask::Classify => [
                    'category' => $this->requiredString($payload, 'category'),
                    'confidence' => $this->requiredConfidence($payload),
                ],
            };
        } catch (AiProviderException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->logFailure('AI provider JSON did not match the expected schema.', $exception);

            throw new AiProviderException(
                'The AI service returned an invalid response.',
                502,
                $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requiredString(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new AiProviderException(
                'The AI service returned an invalid response.',
                502,
            );
        }

        return trim($value);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requiredConfidence(array $payload): float
    {
        $value = $payload['confidence'] ?? null;

        if (! is_numeric($value)) {
            throw new AiProviderException(
                'The AI service returned an invalid response.',
                502,
            );
        }

        $confidence = round((float) $value, 4);

        if ($confidence < 0 || $confidence > 1) {
            throw new AiProviderException(
                'The AI service returned an invalid response.',
                502,
            );
        }

        return $confidence;
    }

    private function temperatureFor(AiTask $task): float
    {
        return match ($task) {
            AiTask::Classify => 0.1,
            AiTask::Summarize => 0.3,
            AiTask::Generate => 0.7,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logFailure(string $message, ?Throwable $exception = null, array $context = []): void
    {
        Log::error($message, array_filter([
            'provider' => 'openai',
            'exception' => $exception?->getMessage(),
            ...$context,
        ], fn ($value) => $value !== null));
    }
}
