<?php

namespace App\Jobs;

use App\Enums\AiDocumentStatus;
use App\Events\AiDocumentCompleted;
use App\Exceptions\EmbeddingException;
use App\Models\AiDocument;
use App\Models\AiDocumentChunk;
use App\Services\AI\EmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateDocumentChunkEmbedding implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    /**
     * @var list<int>
     */
    public array $backoff = [15, 45, 90];

    public function __construct(
        public int $chunkId,
    ) {}

    public function handle(EmbeddingService $embeddingService): void
    {
        $chunk = AiDocumentChunk::query()->find($this->chunkId);

        if ($chunk === null) {
            Log::warning('Chunk embedding skipped because the record was not found.', [
                'chunk_id' => $this->chunkId,
            ]);

            return;
        }

        if ($chunk->isEmbedded()) {
            $this->markDocumentCompletedIfReady($chunk->ai_document_id);

            return;
        }

        if (! filled($chunk->content)) {
            throw new EmbeddingException(
                'Chunk content is empty and cannot be embedded.',
                retryable: false,
            );
        }

        Log::info('Chunk embedding started.', [
            'chunk_id' => $chunk->id,
            'document_id' => $chunk->ai_document_id,
            'provider' => $embeddingService->configuredProvider(),
            'embedding_model' => $embeddingService->configuredModel(),
        ]);

        try {
            $vector = $embeddingService->embed($chunk->content);
        } catch (EmbeddingException $exception) {
            if ($exception->isRetryable()) {
                throw $exception;
            }

            // Permanent failure: mark failed immediately when running on the queue worker.
            if ($this->job !== null) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        }

        $chunk->forceFill([
            'embedding' => $vector,
            'embedding_model' => $embeddingService->configuredModel(),
            'embedded_at' => now(),
        ])->save();

        Log::info('Chunk embedding completed.', [
            'chunk_id' => $chunk->id,
            'document_id' => $chunk->ai_document_id,
            'provider' => $embeddingService->configuredProvider(),
            'embedding_model' => $embeddingService->configuredModel(),
        ]);

        $this->markDocumentCompletedIfReady($chunk->ai_document_id);
    }

    public function failed(?Throwable $exception): void
    {
        $chunk = AiDocumentChunk::query()->find($this->chunkId);

        if ($chunk === null) {
            return;
        }

        Log::error('Chunk embedding failed permanently.', [
            'chunk_id' => $chunk->id,
            'document_id' => $chunk->ai_document_id,
            'exception' => $exception?->getMessage(),
        ]);

        AiDocument::query()
            ->whereKey($chunk->ai_document_id)
            ->where('status', '!=', AiDocumentStatus::Failed->value)
            ->update([
                'status' => AiDocumentStatus::Failed->value,
                'error_message' => $this->safeErrorMessage($exception),
                'updated_at' => now(),
            ]);
    }

    private function markDocumentCompletedIfReady(int $documentId): void
    {
        $updated = AiDocument::query()
            ->whereKey($documentId)
            ->where('status', AiDocumentStatus::Processing->value)
            ->whereHas('chunks')
            ->whereDoesntHave('chunks', function ($query): void {
                $query->whereNull('embedding')
                    ->orWhereNull('embedded_at');
            })
            ->update([
                'status' => AiDocumentStatus::Completed->value,
                'error_message' => null,
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            Log::info('AI document marked completed after all chunk embeddings finished.', [
                'document_id' => $documentId,
            ]);

            $document = AiDocument::query()->find($documentId);

            if ($document !== null) {
                AiDocumentCompleted::dispatch($document);
            }
        }
    }

    private function safeErrorMessage(?Throwable $exception): string
    {
        if ($exception instanceof EmbeddingException) {
            return $exception->getMessage();
        }

        return 'Document embedding failed. Please try again later.';
    }
}
