<?php

namespace App\Jobs;

use App\Enums\AiDocumentStatus;
use App\Exceptions\DocumentExtractionException;
use App\Models\AiDocument;
use App\Services\AI\DocumentTextExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAiDocument implements ShouldQueue
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

    public function handle(DocumentTextExtractor $extractor): void
    {
        $document = AiDocument::query()->find($this->documentId);

        if ($document === null) {
            Log::warning('AI document job skipped because the record was not found.', [
                'document_id' => $this->documentId,
            ]);

            return;
        }

        if ($document->status === AiDocumentStatus::Completed) {
            return;
        }

        // Extraction already finished; continue with chunking only.
        if (filled($document->extracted_text)) {
            ProcessAiDocumentChunks::dispatch($document->id);

            return;
        }

        $document->forceFill([
            'status' => AiDocumentStatus::Processing,
            'error_message' => null,
        ])->save();

        Log::info('AI document extraction started.', [
            'document_id' => $document->id,
            'user_id' => $document->user_id,
            'mime_type' => $document->mime_type,
        ]);

        $extractedText = $extractor->extract($document);

        $document->forceFill([
            'extracted_text' => $extractedText,
            'processed_at' => now(),
            'status' => AiDocumentStatus::Processing,
            'error_message' => null,
        ])->save();

        Log::info('AI document extraction completed.', [
            'document_id' => $document->id,
            'user_id' => $document->user_id,
            'mime_type' => $document->mime_type,
        ]);

        ProcessAiDocumentChunks::dispatch($document->id);
    }

    public function failed(?Throwable $exception): void
    {
        $document = AiDocument::query()->find($this->documentId);

        if ($document === null) {
            return;
        }

        Log::error('AI document extraction failed.', [
            'document_id' => $document->id,
            'user_id' => $document->user_id,
            'mime_type' => $document->mime_type,
            'exception' => $exception?->getMessage(),
        ]);

        $document->forceFill([
            'status' => AiDocumentStatus::Failed,
            'error_message' => $this->safeErrorMessage($exception),
        ])->save();
    }

    private function safeErrorMessage(?Throwable $exception): string
    {
        if ($exception instanceof DocumentExtractionException) {
            return $exception->getMessage();
        }

        return 'Document text extraction failed. Please try again later.';
    }
}
