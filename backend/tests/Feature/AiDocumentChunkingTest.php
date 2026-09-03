<?php

namespace Tests\Feature;

use App\Enums\AiDocumentStatus;
use App\Exceptions\DocumentChunkingException;
use App\Jobs\ProcessAiDocument;
use App\Jobs\ProcessAiDocumentChunks;
use App\Models\AiDocument;
use App\Models\AiDocumentChunk;
use App\Models\User;
use App\Services\AI\DocumentChunker;
use App\Services\AI\DocumentTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Throwable;

class AiDocumentChunkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_document_processing_creates_chunks(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $path = 'ai-documents/'.$user->id.'/'.Str::uuid().'.txt';
        $text = implode(' ', array_map(fn (int $i): string => 'word'.$i, range(1, 40)));

        Storage::disk('local')->put($path, $text);

        config([
            'ai.document_chunking.chunk_size' => 10,
            'ai.document_chunking.overlap' => 2,
        ]);

        $document = AiDocument::factory()->create([
            'user_id' => $user->id,
            'original_name' => 'long.txt',
            'file_path' => $path,
            'mime_type' => 'text/plain',
            'status' => AiDocumentStatus::Uploaded,
        ]);

        (new ProcessAiDocument($document->id))
            ->handle(app(DocumentTextExtractor::class));

        $document->refresh();

        $this->assertSame(AiDocumentStatus::Completed, $document->status);
        $this->assertGreaterThan(1, $document->chunks()->count());
        $this->assertSame(
            range(0, $document->chunks()->count() - 1),
            $document->chunks()->pluck('chunk_index')->all()
        );
        $this->assertNull($document->error_message);
    }

    public function test_chunk_job_is_idempotent_and_does_not_duplicate_chunks(): void
    {
        $user = User::factory()->create();

        $document = AiDocument::factory()->create([
            'user_id' => $user->id,
            'status' => AiDocumentStatus::Processing,
            'extracted_text' => implode(' ', array_map(fn (int $i): string => 'item'.$i, range(1, 30))),
            'processed_at' => now(),
        ]);

        config([
            'ai.document_chunking.chunk_size' => 10,
            'ai.document_chunking.overlap' => 2,
        ]);

        $job = new ProcessAiDocumentChunks($document->id);
        $job->handle(app(DocumentChunker::class));
        $firstCount = $document->chunks()->count();

        $job->handle(app(DocumentChunker::class));

        $this->assertSame($firstCount, $document->chunks()->count());
        $this->assertSame(
            $firstCount,
            AiDocumentChunk::query()->where('ai_document_id', $document->id)->count()
        );
        $this->assertSame(AiDocumentStatus::Completed, $document->fresh()->status);
    }

    public function test_chunk_metadata_is_stored_correctly(): void
    {
        $user = User::factory()->create();

        $document = AiDocument::factory()->create([
            'user_id' => $user->id,
            'status' => AiDocumentStatus::Processing,
            'extracted_text' => 'Alpha beta gamma delta',
            'processed_at' => now(),
        ]);

        (new ProcessAiDocumentChunks($document->id))
            ->handle(app(DocumentChunker::class));

        $chunk = $document->chunks()->first();

        $this->assertNotNull($chunk);
        $this->assertSame(0, $chunk->chunk_index);
        $this->assertNull($chunk->token_count);
        $this->assertSame($document->id, $chunk->metadata['source_document_id']);
        $this->assertSame(0, $chunk->metadata['chunk_index']);
        $this->assertSame(4, $chunk->metadata['word_count']);
        $this->assertSame(mb_strlen($chunk->content), $chunk->metadata['character_count']);
    }

    public function test_chunking_failure_marks_document_failed_safely(): void
    {
        $user = User::factory()->create();

        $document = AiDocument::factory()->create([
            'user_id' => $user->id,
            'status' => AiDocumentStatus::Processing,
            'extracted_text' => null,
            'processed_at' => now(),
        ]);

        $job = new ProcessAiDocumentChunks($document->id);

        try {
            $job->handle(app(DocumentChunker::class));
            $this->fail('Expected chunking to fail.');
        } catch (Throwable $exception) {
            $this->assertInstanceOf(DocumentChunkingException::class, $exception);
            $job->failed($exception);
        }

        $document->refresh();

        $this->assertSame(AiDocumentStatus::Failed, $document->status);
        $this->assertNotNull($document->error_message);
        $this->assertStringNotContainsString('stack', strtolower((string) $document->error_message));
        $this->assertSame(0, $document->chunks()->count());
    }

    public function test_extraction_job_dispatches_chunking_job(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();
        $path = 'ai-documents/'.$user->id.'/'.Str::uuid().'.txt';

        Storage::disk('local')->put($path, 'Short extracted text for dispatch check.');

        $document = AiDocument::factory()->create([
            'user_id' => $user->id,
            'file_path' => $path,
            'mime_type' => 'text/plain',
            'status' => AiDocumentStatus::Uploaded,
        ]);

        (new ProcessAiDocument($document->id))
            ->handle(app(DocumentTextExtractor::class));

        $document->refresh();

        $this->assertSame(AiDocumentStatus::Processing, $document->status);
        $this->assertNotNull($document->extracted_text);
        $this->assertNotNull($document->processed_at);

        Queue::assertPushed(ProcessAiDocumentChunks::class, function (ProcessAiDocumentChunks $job) use ($document): bool {
            return $job->documentId === $document->id;
        });
    }
}
