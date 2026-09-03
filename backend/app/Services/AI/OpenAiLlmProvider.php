<?php

namespace App\Services\AI;

use App\Contracts\AI\LlmProviderInterface;
use App\Exceptions\LlmProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiLlmProvider implements LlmProviderInterface
{
    /**
     * @param  array<string, mixed>  $options
     * @return array{content: string, model: string}
     */
    public function generate(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $apiKey = config('services.openai.key');
        $model = (string) ($options['model'] ?? config('services.openai.model', 'gpt-4.1-mini'));

        if (! is_string($apiKey) || $apiKey === '') {
            throw new LlmProviderException(
                'OpenAI API key is not configured.',
                retryable: false,
            );
        }

        if ($model === '') {
            throw new LlmProviderException(
                'LLM model is not configured.',
                retryable: false,
            );
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => $options['temperature'] ?? 0.2,
        ];

        if (($options['json'] ?? false) === true) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(45)
                ->connectTimeout(10)
                ->post('https://api.openai.com/v1/chat/completions', $payload);
        } catch (ConnectionException $exception) {
            $this->logFailure('LLM provider connection failed.', $exception, [
                'provider' => 'openai',
                'model' => $model,
            ]);

            throw new LlmProviderException(
                'The language model is temporarily unavailable.',
                retryable: true,
                previous: $exception,
            );
        } catch (Throwable $exception) {
            $this->logFailure('LLM provider request crashed.', $exception, [
                'provider' => 'openai',
                'model' => $model,
            ]);

            throw new LlmProviderException(
                'The language model is temporarily unavailable.',
                retryable: true,
                previous: $exception,
            );
        }

        if ($response->failed()) {
            $status = $response->status();
            $retryable = $status === 429 || $status >= 500;

            $this->logFailure('LLM provider request failed.', null, [
                'provider' => 'openai',
                'model' => $model,
                'status' => $status,
                'error' => $response->json('error.message'),
            ]);

            throw new LlmProviderException(
                'The language model is temporarily unavailable.',
                retryable: $retryable,
            );
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new LlmProviderException(
                'The language model returned an empty response.',
                retryable: false,
            );
        }

        return [
            'content' => $content,
            'model' => (string) data_get($response->json(), 'model', $model),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logFailure(string $message, ?Throwable $exception = null, array $context = []): void
    {
        Log::error($message, array_filter([
            'exception' => $exception?->getMessage(),
            ...$context,
        ], static fn ($value) => $value !== null));
    }
}
