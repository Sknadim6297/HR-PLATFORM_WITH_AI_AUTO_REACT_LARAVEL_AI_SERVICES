<?php

namespace App\Services\AI;

use App\Contracts\AI\LlmProviderInterface;
use App\Exceptions\LlmProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiLlmProvider implements LlmProviderInterface
{
    /**
     * @param  array<string, mixed>  $options
     * @return array{content: string, model: string}
     */
    public function generate(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $apiKey = config('services.gemini.key');
        $model = (string) ($options['model'] ?? config('services.gemini.model', 'gemini-2.0-flash'));

        if (! is_string($apiKey) || $apiKey === '') {
            throw new LlmProviderException(
                'Gemini API key is not configured.',
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
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userPrompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.2,
            ],
        ];

        if (($options['json'] ?? false) === true) {
            $payload['generationConfig']['responseMimeType'] = 'application/json';
        }

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            rawurlencode($model),
        );

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
            ])
                ->acceptJson()
                ->timeout(45)
                ->connectTimeout(10)
                ->post($url, $payload);
        } catch (ConnectionException $exception) {
            $this->logFailure('LLM provider connection failed.', $exception, [
                'provider' => 'gemini',
                'model' => $model,
            ]);

            throw new LlmProviderException(
                'The language model is temporarily unavailable.',
                retryable: true,
                previous: $exception,
            );
        } catch (Throwable $exception) {
            $this->logFailure('LLM provider request crashed.', $exception, [
                'provider' => 'gemini',
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
                'provider' => 'gemini',
                'model' => $model,
                'status' => $status,
                'error' => $response->json('error.message'),
            ]);

            throw new LlmProviderException(
                'The language model is temporarily unavailable.',
                retryable: $retryable,
            );
        }

        $parts = data_get($response->json(), 'candidates.0.content.parts');
        $content = '';

        if (is_array($parts)) {
            foreach ($parts as $part) {
                if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                    $content .= $part['text'];
                }
            }
        }

        if (trim($content) === '') {
            throw new LlmProviderException(
                'The language model returned an empty response.',
                retryable: false,
            );
        }

        return [
            'content' => $content,
            'model' => $model,
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
