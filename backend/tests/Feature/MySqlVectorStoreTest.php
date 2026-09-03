<?php

namespace Tests\Feature;

use App\Enums\AiDocumentStatus;
use App\Models\AiDocument;
use App\Models\AiDocumentChunk;
use App\Models\User;
use App\Services\AI\MySqlVectorStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MySqlVectorStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_ranks_similar_chunks_higher_and_respects_top_k(): void
    {
        $user = User::factory()->create();
        $document = $this->documentFor($user);

        $chunkA = $this->chunk($document, 0, 'A', [1, 0]);
        $chunkB = $this->chunk($document, 1, 'B', [0, 1]);
        $chunkC = $this->chunk($document, 2, 'C', [0.9, 0.1]);

        $results = app(MySqlVectorStore::class)->search(
            [1, 0],
            [$document->id],
            2,
            0.0,
        );

        $this->assertCount(2, $results);
        $this->assertSame($chunkA->id, $results[0]['chunk_id']);
        $this->assertSame($chunkC->id, $results[1]['chunk_id']);
        $this->assertTrue($results[0]['score'] > $results[1]['score']);
        $this->assertNotContains($chunkB->id, array_column($results, 'chunk_id'));
    }

    public function test_it_ignores_chunks_without_embeddings(): void
    {
        $user = User::factory()->create();
        $document = $this->documentFor($user);

        $this->chunk($document, 0, 'embedded', [1, 0]);
        AiDocumentChunk::query()->create([
            'ai_document_id' => $document->id,
            'chunk_index' => 1,
            'content' => 'missing embedding',
            'embedding' => null,
            'embedded_at' => null,
        ]);

        $results = app(MySqlVectorStore::class)->search(
            [1, 0],
            [$document->id],
            5,
            0.0,
        );

        $this->assertCount(1, $results);
        $this->assertSame('embedded', $results[0]['content']);
    }

    public function test_minimum_score_filters_weak_matches(): void
    {
        $user = User::factory()->create();
        $document = $this->documentFor($user);

        $this->chunk($document, 0, 'strong', [1, 0]);
        $this->chunk($document, 1, 'weak', [0, 1]);

        $results = app(MySqlVectorStore::class)->search(
            [1, 0],
            [$document->id],
            5,
            0.5,
        );

        $this->assertCount(1, $results);
        $this->assertSame('strong', $results[0]['content']);
    }

    public function test_document_filtering_and_empty_scope(): void
    {
        $user = User::factory()->create();
        $docA = $this->documentFor($user);
        $docB = $this->documentFor($user);

        $this->chunk($docA, 0, 'only A', [1, 0]);
        $this->chunk($docB, 0, 'only B', [1, 0]);

        $results = app(MySqlVectorStore::class)->search(
            [1, 0],
            [$docA->id],
            5,
            0.0,
        );

        $this->assertCount(1, $results);
        $this->assertSame($docA->id, $results[0]['document_id']);

        $this->assertSame([], app(MySqlVectorStore::class)->search([1, 0], [], 5, 0.0));
    }

    public function test_invalid_and_mismatched_vectors_are_skipped_safely(): void
    {
        $user = User::factory()->create();
        $document = $this->documentFor($user);

        $this->chunk($document, 0, 'good', [1, 0]);
        $this->chunk($document, 1, 'bad-dim', [1, 0, 0]);
        $this->chunk($document, 2, 'zero', [0, 0]);

        $results = app(MySqlVectorStore::class)->search(
            [1, 0],
            [$document->id],
            5,
            0.0,
        );

        $this->assertCount(1, $results);
        $this->assertSame('good', $results[0]['content']);
        $this->assertArrayNotHasKey('embedding', $results[0]);
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
