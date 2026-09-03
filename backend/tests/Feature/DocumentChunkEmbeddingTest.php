<?php

namespace Tests\Feature;

use App\Enums\AiDocumentStatus;
use App\Exceptions\EmbeddingException;
use App\Jobs\GenerateDocumentChunkEmbedding;
use App\Jobs\ProcessAiDocumentChunks;
use App\Models\AiDocument;
use App\Models\AiDocumentChunk;
use App\Models\User;
use App\Services\AI\DocumentChunker;
use App\Services\AI\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Throwable;

class DocumentChunkEmbeddingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openai.key' => 'test-openai-key',
            'ai.embeddings.provider' => 'openai',
            'ai.embeddings.model' => 'text-embedding-3-small',
            'ai.document_chunking.chunk_size' => 10,
            'ai.document_chunking.overlap' => 2,
        ]);
    }

    public function test_embedding_job_persists_vector_model_and_timestamp(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response($this->embeddingPayload([0.11, 0.22, 0.33]), 200),
        ]);

        $chunk = $this->makeChunk('Embedding content');

        (new GenerateDocumentChunkEmbedding($chunk->id))
            ->handle(app(EmbeddingService::class));

        $chunk->refresh();

        $this->assertSame([0.11, 0.22, 0.33], $chunk->embedding);
        $this->assertSame('text-embedding-3-small', $chunk->embedding_model);
        $this->assertNotNull($chunk->embedded_at);
        $this->assertTrue($chunk->isEmbedded());
        $this->assertSame(AiDocumentStatus::Completed, $chunk->document->fresh()->status);
    }

    public function test_already_embedded_chunk_is_skipped(): void
    {
        Http::fake();

        $chunk = $this->makeChunk('Already embedded', [
            'embedding' => [0.5, 0.6],
            'embedding_model' => 'text-embedding-3-small',
            'embedded_at' => now(),
        ]);

        $document = $chunk->document;
        $document->forceFill(['status' => AiDocumentStatus::Processing])->save();

        (new GenerateDocumentChunkEmbedding($chunk->id))
            ->handle(app(EmbeddingService::class));

        Http::assertNothingSent();
        $this->assertSame([0.5, 0.6], $chunk->fresh()->embedding);
        $this->assertSame(AiDocumentStatus::Completed, $document->fresh()->status);
    }

    public function test_chunking_dispatches_one_embedding_job_per_chunk(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $document = AiDocument::factory()->create([
            'user_id' => $user->id,
            'status' => AiDocumentStatus::Processing,
            'extracted_text' => implode(' ', array_map(fn (int $i): string => 'word'.$i, range(1, 25))),
            'processed_at' => now(),
        ]);

        (new ProcessAiDocumentChunks($document->id))
            ->handle(app(DocumentChunker::class));

        $document->refresh();

        $this->assertSame(AiDocumentStatus::Processing, $document->status);
        $this->assertGreaterThan(1, $document->chunks()->count());

        Queue::assertPushed(GenerateDocumentChunkEmbedding::class, $document->chunks()->count());
    }

    public function test_document_stays_processing_until_all_chunks_are_embedded(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response($this->embeddingPayload([0.1, 0.2]), 200),
        ]);

        $user = User::factory()->create();
        $document = AiDocument::factory()->create([
            'user_id' => $user->id,
            'status' => AiDocumentStatus::Processing,
            'extracted_text' => 'doc',
            'processed_at' => now(),
        ]);

        $first = AiDocumentChunk::query()->create([
            'ai_document_id' => $document->id,
            'chunk_index' => 0,
            'content' => 'First chunk',
        ]);
        $second = AiDocumentChunk::query()->create([
            'ai_document_id' => $document->id,
            'chunk_index' => 1,
            'content' => 'Second chunk',
        ]);

        (new GenerateDocumentChunkEmbedding($first->id))
            ->handle(app(EmbeddingService::class));

        $this->assertSame(AiDocumentStatus::Processing, $document->fresh()->status);
        $this->assertTrue($first->fresh()->isEmbedded());
        $this->assertFalse($second->fresh()->isEmbedded());

        (new GenerateDocumentChunkEmbedding($second->id))
            ->handle(app(EmbeddingService::class));

        $this->assertSame(AiDocumentStatus::Completed, $document->fresh()->status);
        $this->assertTrue($second->fresh()->isEmbedded());
    }

    public function test_permanent_embedding_failure_marks_document_failed(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response(['error' => ['message' => 'bad request']], 400),
        ]);

        $chunk = $this->makeChunk('Bad chunk');
        $job = new GenerateDocumentChunkEmbedding($chunk->id);

        try {
            $job->handle(app(EmbeddingService::class));
            $this->fail('Expected embedding failure.');
        } catch (Throwable $exception) {
            $this->assertInstanceOf(EmbeddingException::class, $exception);
            $this->assertFalse($exception->isRetryable());
            $job->failed($exception);
        }

        $chunk->refresh();
        $document = $chunk->document()->first();

        $this->assertNull($chunk->embedding);
        $this->assertNull($chunk->embedded_at);
        $this->assertSame(AiDocumentStatus::Failed, $document->status);
        $this->assertNotNull($document->error_message);
        $this->assertStringNotContainsString('test-openai-key', (string) $document->error_message);
    }

    /**
     * @param  list<float>  $vector
     * @return array<string, mixed>
     */
    private function embeddingPayload(array $vector): array
    {
        return [
            'data' => [
                [
                    'embedding' => $vector,
                    'index' => 0,
                ],
            ],
            'model' => 'text-embedding-3-small',
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeChunk(string $content, array $overrides = []): AiDocumentChunk
    {
        $user = User::factory()->create();

        $document = AiDocument::factory()->create([
            'user_id' => $user->id,
            'status' => AiDocumentStatus::Processing,
            'extracted_text' => $content,
            'processed_at' => now(),
        ]);

        return AiDocumentChunk::query()->create(array_merge([
            'ai_document_id' => $document->id,
            'chunk_index' => 0,
            'content' => $content,
        ], $overrides));
    }
}
