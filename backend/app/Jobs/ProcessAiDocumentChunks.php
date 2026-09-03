<?php

namespace App\Jobs;

use App\Enums\AiDocumentStatus;
use App\Exceptions\DocumentChunkingException;
use App\Models\AiDocument;
use App\Services\AI\DocumentChunker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAiDocumentChunks implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    /**
     * @var list<int>
     */
    public array $backoff = [15, 45];

    public function __construct(
        public int $documentId,
    ) {}

    public function handle(DocumentChunker $chunker): void
    {
        $document = AiDocument::query()->find($this->documentId);

        if ($document === null) {
            Log::warning('AI document chunking skipped because the record was not found.', [
                'document_id' => $this->documentId,
            ]);

            return;
        }

        if ($document->status === AiDocumentStatus::Completed) {
            return;
        }

        if (! filled($document->extracted_text)) {
            throw new DocumentChunkingException(
                'Document text is missing and cannot be chunked.'
            );
        }

        // Chunks already exist (e.g. retry after partial embedding) — only embed missing ones.
        if ($document->chunks()->exists()) {
            $this->dispatchEmbeddingJobs($document);

            return;
        }

        Log::info('AI document chunking started.', [
            'document_id' => $document->id,
            'user_id' => $document->user_id,
        ]);

        $chunks = $chunker->chunk($document->extracted_text, $document->id);

        if ($chunks === []) {
            throw new DocumentChunkingException(
                'No chunks could be created from the document text.'
            );
        }

        DB::transaction(function () use ($document, $chunks): void {
            $document->chunks()->delete();

            foreach ($chunks as $chunk) {
                $document->chunks()->create([
                    'chunk_index' => $chunk['chunk_index'],
                    'content' => $chunk['content'],
                    'token_count' => $chunk['token_count'],
                    'metadata' => $chunk['metadata'],
                ]);
            }

            $document->forceFill([
                'status' => AiDocumentStatus::Processing,
                'error_message' => null,
            ])->save();
        });

        Log::info('AI document chunking completed.', [
            'document_id' => $document->id,
            'user_id' => $document->user_id,
            'chunk_count' => $document->chunks()->count(),
        ]);

        $this->dispatchEmbeddingJobs($document->fresh());
    }

    public function failed(?Throwable $exception): void
    {
        $document = AiDocument::query()->find($this->documentId);

        if ($document === null) {
            return;
        }

        Log::error('AI document chunking failed.', [
            'document_id' => $document->id,
            'user_id' => $document->user_id,
            'exception' => $exception?->getMessage(),
        ]);

        $document->forceFill([
            'status' => AiDocumentStatus::Failed,
            'error_message' => $this->safeErrorMessage($exception),
        ])->save();
    }

    private function dispatchEmbeddingJobs(?AiDocument $document): void
    {
        if ($document === null) {
            return;
        }

        $chunkIds = $document->chunks()
            ->where(function ($query): void {
                $query->whereNull('embedding')
                    ->orWhereNull('embedded_at');
            })
            ->pluck('id');

        if ($chunkIds->isEmpty()) {
            AiDocument::query()
                ->whereKey($document->id)
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

            return;
        }

        foreach ($chunkIds as $chunkId) {
            GenerateDocumentChunkEmbedding::dispatch((int) $chunkId);
        }

        Log::info('AI document embedding jobs dispatched.', [
            'document_id' => $document->id,
            'pending_chunk_count' => $chunkIds->count(),
        ]);
    }

    private function safeErrorMessage(?Throwable $exception): string
    {
        if ($exception instanceof DocumentChunkingException) {
            return $exception->getMessage();
        }

        return 'Document chunking failed. Please try again later.';
    }
}
