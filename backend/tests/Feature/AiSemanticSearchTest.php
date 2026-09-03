<?php

namespace Tests\Feature;

use App\Enums\AiDocumentStatus;
use App\Models\AiDocument;
use App\Models\AiDocumentChunk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiSemanticSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openai.key' => 'test-openai-key',
            'ai.embeddings.provider' => 'openai',
            'ai.embeddings.model' => 'text-embedding-3-small',
            'ai.vector_search.top_k' => 5,
            'ai.vector_search.min_score' => 0.0,
        ]);
    }

    public function test_unauthenticated_search_returns_401(): void
    {
        $this->postJson('/api/ai/search', [
            'query' => 'Laravel experience',
        ])->assertUnauthorized();
    }

    public function test_authenticated_search_returns_ranked_results(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response([
                'data' => [['embedding' => [1, 0], 'index' => 0]],
                'model' => 'text-embedding-3-small',
            ], 200),
        ]);

        $user = User::factory()->create();
        $document = $this->documentFor($user);
        $best = $this->chunk($document, 0, 'Laravel expert', [1, 0]);
        $mid = $this->chunk($document, 1, 'Mostly Laravel', [0.9, 0.1]);
        $this->chunk($document, 2, 'Unrelated', [0, 1]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/search', [
            'query' => 'Laravel developer experience',
            'document_id' => $document->id,
            'top_k' => 2,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.chunk_id', $best->id)
            ->assertJsonPath('data.1.chunk_id', $mid->id)
            ->assertJsonMissingPath('data.0.embedding')
            ->assertJsonMissingPath('data.0.file_path')
            ->assertJsonMissingPath('data.0.extracted_text');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.openai.com/v1/embeddings'
                && $request['input'] === 'Laravel developer experience';
        });
    }

    public function test_query_validation_and_top_k_bounds(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/ai/search', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query']);

        $this->postJson('/api/ai/search', [
            'query' => 'ok',
            'top_k' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors(['top_k']);

        $this->postJson('/api/ai/search', [
            'query' => 'ok',
            'top_k' => 21,
        ])->assertUnprocessable()->assertJsonValidationErrors(['top_k']);
    }

    public function test_user_cannot_search_another_users_document(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response([
                'data' => [['embedding' => [1, 0], 'index' => 0]],
                'model' => 'text-embedding-3-small',
            ], 200),
        ]);

        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $document = $this->documentFor($owner);
        $this->chunk($document, 0, 'secret', [1, 0]);

        Sanctum::actingAs($intruder);

        $this->postJson('/api/ai/search', [
            'query' => 'secret resume details',
            'document_id' => $document->id,
        ])
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Document not found.');
    }

    public function test_empty_results_return_success_with_empty_data(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response([
                'data' => [['embedding' => [1, 0], 'index' => 0]],
                'model' => 'text-embedding-3-small',
            ], 200),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/ai/search', [
            'query' => 'anything',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_provider_failure_returns_safe_error(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response(['error' => ['message' => 'down']], 503),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/ai/search', [
            'query' => 'Laravel',
        ])
            ->assertStatus(502)
            ->assertJsonPath('success', false)
            ->assertJsonMissingPath('error.message');
    }

    private function documentFor(User $user): AiDocument
    {
        return AiDocument::factory()->create([
            'user_id' => $user->id,
            'status' => AiDocumentStatus::Completed,
            'extracted_text' => 'text',
            'processed_at' => now(),
        ]);
    }

    /**
     * @param  list<float|int>  $embedding
     */
    private function chunk(AiDocument $document, int $index, string $content, array $embedding): AiDocumentChunk
    {
        return AiDocumentChunk::query()->create([
            'ai_document_id' => $document->id,
            'chunk_index' => $index,
            'content' => $content,
            'embedding' => $embedding,
            'embedding_model' => 'text-embedding-3-small',
            'embedded_at' => now(),
        ]);
    }
}
