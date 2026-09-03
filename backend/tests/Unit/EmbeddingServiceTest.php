<?php

namespace Tests\Unit;

use App\Exceptions\EmbeddingException;
use App\Services\AI\EmbeddingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openai.key' => 'test-openai-key',
            'ai.embeddings.provider' => 'openai',
            'ai.embeddings.model' => 'text-embedding-3-small',
        ]);
    }

    public function test_it_extracts_a_valid_embedding_vector(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response([
                'data' => [
                    [
                        'embedding' => [0.1, -0.2, 0.3],
                        'index' => 0,
                    ],
                ],
                'model' => 'text-embedding-3-small',
            ], 200),
        ]);

        $vector = app(EmbeddingService::class)->embed('Hello embeddings');

        $this->assertSame([0.1, -0.2, 0.3], $vector);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.openai.com/v1/embeddings'
                && $request['model'] === 'text-embedding-3-small'
                && $request['input'] === 'Hello embeddings';
        });
    }

    public function test_it_rejects_empty_input(): void
    {
        $this->expectException(EmbeddingException::class);
        $this->expectExceptionMessage('Embedding input text cannot be empty.');

        app(EmbeddingService::class)->embed('   ');
    }

    public function test_it_rejects_missing_embedding(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response([
                'data' => [
                    ['index' => 0],
                ],
            ], 200),
        ]);

        try {
            app(EmbeddingService::class)->embed('Hello');
            $this->fail('Expected EmbeddingException.');
        } catch (EmbeddingException $exception) {
            $this->assertFalse($exception->isRetryable());
        }
    }

    public function test_it_rejects_non_numeric_embedding_values(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response([
                'data' => [
                    [
                        'embedding' => [0.1, 'bad', 0.3],
                        'index' => 0,
                    ],
                ],
            ], 200),
        ]);

        $this->expectException(EmbeddingException::class);

        app(EmbeddingService::class)->embed('Hello');
    }

    public function test_http_failure_is_retryable_for_server_errors(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response(['error' => ['message' => 'boom']], 503),
        ]);

        try {
            app(EmbeddingService::class)->embed('Hello');
            $this->fail('Expected EmbeddingException.');
        } catch (EmbeddingException $exception) {
            $this->assertTrue($exception->isRetryable());
        }
    }

    public function test_http_failure_is_not_retryable_for_client_errors(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response(['error' => ['message' => 'bad request']], 400),
        ]);

        try {
            app(EmbeddingService::class)->embed('Hello');
            $this->fail('Expected EmbeddingException.');
        } catch (EmbeddingException $exception) {
            $this->assertFalse($exception->isRetryable());
        }
    }

    public function test_missing_api_key_is_not_retryable(): void
    {
        config(['services.openai.key' => null]);

        try {
            app(EmbeddingService::class)->embed('Hello');
            $this->fail('Expected EmbeddingException.');
        } catch (EmbeddingException $exception) {
            $this->assertFalse($exception->isRetryable());
            $this->assertStringContainsString('API key', $exception->getMessage());
        }
    }

    public function test_unsupported_provider_is_not_retryable(): void
    {
        config(['ai.embeddings.provider' => 'unknown-provider']);

        try {
            app(EmbeddingService::class)->embed('Hello');
            $this->fail('Expected EmbeddingException.');
        } catch (EmbeddingException $exception) {
            $this->assertFalse($exception->isRetryable());
        }
    }
}
