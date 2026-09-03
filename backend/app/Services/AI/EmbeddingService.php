<?php

namespace App\Services\AI;

use App\Exceptions\EmbeddingException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmbeddingService
{
    /**
     * Generate an embedding vector for the given text.
     *
     * @return list<float|int>
     */
    public function embed(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            throw new EmbeddingException(
                'Embedding input text cannot be empty.',
                retryable: false,
            );
        }

        $provider = (string) config('ai.embeddings.provider', 'openai');
        $model = (string) config('ai.embeddings.model', 'text-embedding-3-small');

        if ($model === '') {
            throw new EmbeddingException(
                'Embedding model is not configured.',
                retryable: false,
            );
        }

        return match ($provider) {
            'openai' => $this->embedWithOpenAi($text, $model),
            default => throw new EmbeddingException(
                "Unsupported embedding provider [{$provider}].",
                retryable: false,
            ),
        };
    }

    public function configuredModel(): string
    {
        return (string) config('ai.embeddings.model', 'text-embedding-3-small');
    }

    public function configuredProvider(): string
    {
        return (string) config('ai.embeddings.provider', 'openai');
    }

    /**
     * @return list<float|int>
     */
    private function embedWithOpenAi(string $text, string $model): array
    {
        $apiKey = config('services.openai.key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new EmbeddingException(
                'OpenAI API key is not configured.',
                retryable: false,
            );
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(30)
                ->connectTimeout(10)
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => $model,
                    'input' => $text,
                ]);
        } catch (ConnectionException $exception) {
            $this->logFailure('Embedding provider connection failed.', $exception, [
                'provider' => 'openai',
                'model' => $model,
            ]);

            throw new EmbeddingException(
                'The embedding service is temporarily unavailable.',
                retryable: true,
                previous: $exception,
            );
        } catch (Throwable $exception) {
            $this->logFailure('Embedding provider request crashed.', $exception, [
                'provider' => 'openai',
                'model' => $model,
            ]);

            throw new EmbeddingException(
                'The embedding service is temporarily unavailable.',
                retryable: true,
                previous: $exception,
            );
        }

        if ($response->failed()) {
            $status = $response->status();
            $retryable = $status === 429 || $status >= 500;

            $this->logFailure('Embedding provider request failed.', null, [
                'provider' => 'openai',
                'model' => $model,
                'status' => $status,
                'error' => $response->json('error.message'),
            ]);

            throw new EmbeddingException(
                'The embedding service is temporarily unavailable.',
                retryable: $retryable,
            );
        }

        $embedding = data_get($response->json(), 'data.0.embedding');

        return $this->validateEmbeddingVector($embedding);
    }

    /**
     * @return list<float|int>
     */
    private function validateEmbeddingVector(mixed $embedding): array
    {
        if (! is_array($embedding) || $embedding === []) {
            throw new EmbeddingException(
                'The embedding provider returned an invalid response.',
                retryable: false,
            );
        }

        $vector = [];

        foreach ($embedding as $value) {
            if (! is_int($value) && ! is_float($value)) {
                throw new EmbeddingException(
                    'The embedding provider returned non-numeric vector values.',
                    retryable: false,
                );
            }

            $vector[] = $value;
        }

        return $vector;
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
