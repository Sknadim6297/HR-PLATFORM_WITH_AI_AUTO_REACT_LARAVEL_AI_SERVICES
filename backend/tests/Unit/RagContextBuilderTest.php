<?php

namespace Tests\Unit;

use App\Services\AI\RagContextBuilder;
use Tests\TestCase;

class RagContextBuilderTest extends TestCase
{
    public function test_it_builds_separated_chunk_context_without_embeddings(): void
    {
        config([
            'ai.rag.max_context_chunks' => 5,
            'ai.rag.max_context_characters' => 12000,
        ]);

        $context = app(RagContextBuilder::class)->build([
            [
                'chunk_id' => 12,
                'document_id' => 10,
                'chunk_index' => 3,
                'score' => 0.84,
                'content' => 'Laravel experience for five years.',
                'embedding' => [1, 0],
            ],
            [
                'chunk_id' => 15,
                'document_id' => 10,
                'chunk_index' => 4,
                'score' => 0.79,
                'content' => 'Built recruitment APIs.',
            ],
        ]);

        $this->assertStringContainsString('DOCUMENT 10', $context);
        $this->assertStringContainsString('CHUNK 3', $context);
        $this->assertStringContainsString('RELEVANCE: 0.8400', $context);
        $this->assertStringContainsString('Laravel experience for five years.', $context);
        $this->assertStringContainsString('---', $context);
        $this->assertStringNotContainsString('embedding', $context);
        $this->assertStringNotContainsString('chunk_id', $context);
    }

    public function test_it_respects_max_context_chunks_and_characters(): void
    {
        config([
            'ai.rag.max_context_chunks' => 1,
            'ai.rag.max_context_characters' => 12000,
        ]);

        $context = app(RagContextBuilder::class)->build([
            [
                'chunk_id' => 1,
                'document_id' => 1,
                'chunk_index' => 0,
                'score' => 0.9,
                'content' => 'First chunk',
            ],
            [
                'chunk_id' => 2,
                'document_id' => 1,
                'chunk_index' => 1,
                'score' => 0.8,
                'content' => 'Second chunk',
            ],
        ]);

        $this->assertStringContainsString('First chunk', $context);
        $this->assertStringNotContainsString('Second chunk', $context);
    }
}
