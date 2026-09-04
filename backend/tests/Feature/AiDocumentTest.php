<?php

namespace Tests\Feature;

use App\Enums\AiDocumentStatus;
use App\Jobs\ProcessAiDocument;
use App\Models\AiDocument;
use App\Models\User;
use App\Services\AI\DocumentTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Throwable;

class AiDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_pdf(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('resume.pdf', 150, 'application/pdf');

        $response = $this->post('/api/ai/documents', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Document uploaded successfully.')
            ->assertJsonPath('data.original_name', 'resume.pdf')
            ->assertJsonPath('data.mime_type', 'application/pdf')
            ->assertJsonPath('data.status', AiDocumentStatus::Uploaded->value)
            ->assertJsonPath('data.processed_at', null)
            ->assertJsonPath('data.chunk_count', 0)
            ->assertJsonMissingPath('data.file_path')
            ->assertJsonMissingPath('data.extracted_text');

        $document = AiDocument::query()->first();

        $this->assertNotNull($document);
        $this->assertSame($user->id, $document->user_id);
        $this->assertSame('resume.pdf', $document->original_name);
        $this->assertSame('application/pdf', $document->mime_type);
        $this->assertSame(AiDocumentStatus::Uploaded, $document->status);
        $this->assertNotNull($document->file_size);
        $this->assertStringStartsWith('ai-documents/'.$user->id.'/', $document->file_path);
        $this->assertStringNotContainsString('resume.pdf', $document->file_path);

        Storage::disk('local')->assertExists($document->file_path);

        Queue::assertPushed(ProcessAiDocument::class, function (ProcessAiDocument $job) use ($document): bool {
            return $job->documentId === $document->id;
        });
    }

    public function test_upload_dispatches_process_ai_document_job(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->post('/api/ai/documents', [
            'file' => UploadedFile::fake()->create('notes.txt', 5, 'text/plain'),
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        Queue::assertPushed(ProcessAiDocument::class);
    }

    public function test_unauthenticated_upload_returns_401(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('resume.pdf', 150, 'application/pdf');

        $this->post('/api/ai/documents', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ])->assertUnauthorized();
    }

    public function test_invalid_file_returns_422(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('photo.jpg', 50, 'image/jpeg');

        $this->post('/api/ai/documents', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        Queue::assertNothingPushed();
    }

    public function test_user_can_list_only_their_own_documents(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $own = AiDocument::factory()->create([
            'user_id' => $user->id,
            'original_name' => 'mine.pdf',
        ]);

        AiDocument::factory()->create([
            'user_id' => $other->id,
            'original_name' => 'theirs.pdf',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/ai/documents')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id)
            ->assertJsonPath('data.0.original_name', 'mine.pdf')
            ->assertJsonMissingPath('data.0.file_path')
            ->assertJsonMissingPath('data.0.extracted_text')
            ->assertJsonFragment(['original_name' => 'mine.pdf'])
            ->assertJsonMissing(['original_name' => 'theirs.pdf']);
    }

    public function test_user_cannot_access_another_users_document(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $foreign = AiDocument::factory()->create([
            'user_id' => $other->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/ai/documents/'.$foreign->id)
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Document not found.');
    }

    public function test_hr_can_view_resume_document_attached_to_application(): void
    {
        $candidate = User::factory()->candidate()->create();
        $hr = User::factory()->hr()->create();

        $document = AiDocument::factory()->create([
            'user_id' => $candidate->id,
            'original_name' => 'candidate-resume.pdf',
            'status' => AiDocumentStatus::Processing,
        ]);

        $job = \App\Models\Job::factory()->published()->create([
            'created_by' => $hr->id,
        ]);

        \App\Models\JobApplication::factory()->create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'resume_document_id' => $document->id,
        ]);

        Sanctum::actingAs($hr);

        $this->getJson('/api/ai/documents/'.$document->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $document->id)
            ->assertJsonPath('data.original_name', 'candidate-resume.pdf')
            ->assertJsonPath('data.status', AiDocumentStatus::Processing->value)
            ->assertJsonMissingPath('data.file_path');
    }

    public function test_candidate_still_cannot_view_unrelated_foreign_document(): void
    {
        $candidate = User::factory()->candidate()->create();
        $other = User::factory()->candidate()->create();

        $foreign = AiDocument::factory()->create([
            'user_id' => $other->id,
        ]);

        Sanctum::actingAs($candidate);

        $this->getJson('/api/ai/documents/'.$foreign->id)
            ->assertNotFound();
    }

    public function test_user_can_view_their_own_document_metadata(): void
    {
        $user = User::factory()->create();

        $document = AiDocument::factory()->create([
            'user_id' => $user->id,
            'original_name' => 'offer-letter.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size' => 2048,
            'processed_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/ai/documents/'.$document->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $document->id)
            ->assertJsonPath('data.original_name', 'offer-letter.docx')
            ->assertJsonPath('data.mime_type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertJsonPath('data.file_size', 2048)
            ->assertJsonPath('data.status', 'uploaded')
            ->assertJsonPath('data.processed_at', $document->processed_at->toJSON())
            ->assertJsonPath('data.chunk_count', 0)
            ->assertJsonMissingPath('data.file_path')
            ->assertJsonMissingPath('data.extracted_text');
    }

    public function test_successful_txt_extraction_completes_document(): void
    {
        Storage::fake('local');
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response([
                'data' => [['embedding' => [0.01, 0.02, 0.03], 'index' => 0]],
                'model' => 'text-embedding-3-small',
            ], 200),
        ]);

        config([
            'services.openai.key' => 'test-openai-key',
            'ai.embeddings.provider' => 'openai',
            'ai.embeddings.model' => 'text-embedding-3-small',
        ]);

        $user = User::factory()->create();
        $path = 'ai-documents/'.$user->id.'/'.Str::uuid().'.txt';
        $expected = 'Candidate summary: Nadim is a Laravel developer.';

        Storage::disk('local')->put($path, $expected."\n");

        $document = AiDocument::factory()->create([
            'user_id' => $user->id,
            'original_name' => 'notes.txt',
            'file_path' => $path,
            'mime_type' => 'text/plain',
            'status' => AiDocumentStatus::Uploaded,
            'extracted_text' => null,
            'processed_at' => null,
        ]);

        (new ProcessAiDocument($document->id))
            ->handle(app(DocumentTextExtractor::class));

        $document->refresh();

        $this->assertSame(AiDocumentStatus::Completed, $document->status);
        $this->assertStringContainsString('Laravel developer', (string) $document->extracted_text);
        $this->assertNotNull($document->processed_at);
        $this->assertNull($document->error_message);
        $this->assertSame(1, $document->chunks()->count());
        $this->assertTrue($document->chunks()->first()->isEmbedded());
    }

    public function test_failed_extraction_marks_document_failed_safely(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $path = 'ai-documents/'.$user->id.'/'.Str::uuid().'.pdf';

        Storage::disk('local')->put($path, 'this-is-not-a-valid-pdf');

        $document = AiDocument::factory()->create([
            'user_id' => $user->id,
            'original_name' => 'broken.pdf',
            'file_path' => $path,
            'mime_type' => 'application/pdf',
            'status' => AiDocumentStatus::Uploaded,
        ]);

        $job = new ProcessAiDocument($document->id);

        try {
            $job->handle(app(DocumentTextExtractor::class));
            $this->fail('Expected document extraction to fail.');
        } catch (Throwable $exception) {
            $job->failed($exception);
        }

        $document->refresh();

        $this->assertSame(AiDocumentStatus::Failed, $document->status);
        $this->assertNotNull($document->error_message);
        $this->assertStringNotContainsString('Storage', (string) $document->error_message);
        $this->assertStringNotContainsString($path, (string) $document->error_message);
        $this->assertStringNotContainsString('stack', strtolower((string) $document->error_message));
        $this->assertNull($document->extracted_text);
    }
}
