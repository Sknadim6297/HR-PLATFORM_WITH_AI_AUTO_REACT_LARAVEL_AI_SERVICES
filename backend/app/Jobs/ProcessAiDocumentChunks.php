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

        if ($document->status === AiDocumentStatus::Completed
            && $document->chunks()->exists()
        ) {
            return;
        }

        if (! filled($document->extracted_text)) {
            throw new DocumentChunkingException(
                'Document text is missing and cannot be chunked.'
            );
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
                'status' => AiDocumentStatus::Completed,
                'error_message' => null,
            ])->save();
        });

        Log::info('AI document chunking completed.', [
            'document_id' => $document->id,
            'user_id' => $document->user_id,
            'chunk_count' => count($chunks),
        ]);
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

    private function safeErrorMessage(?Throwable $exception): string
    {
        if ($exception instanceof DocumentChunkingException) {
            return $exception->getMessage();
        }

        return 'Document chunking failed. Please try again later.';
    }
}
