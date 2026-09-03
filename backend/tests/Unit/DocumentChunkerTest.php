<?php

namespace Tests\Unit;

use App\Exceptions\DocumentChunkingException;
use App\Services\AI\DocumentChunker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentChunkerTest extends TestCase
{
    #[Test]
    public function it_splits_long_text_into_multiple_ordered_chunks(): void
    {
        $chunker = new DocumentChunker(chunkSize: 10, overlap: 2);
        $words = [];

        for ($i = 1; $i <= 25; $i++) {
            $words[] = 'word'.$i;
        }

        $chunks = $chunker->chunk(implode(' ', $words), 42);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertSame(0, $chunks[0]['chunk_index']);
        $this->assertSame(1, $chunks[1]['chunk_index']);

        $indexes = array_column($chunks, 'chunk_index');
        $this->assertSame(range(0, count($chunks) - 1), $indexes);
    }

    #[Test]
    public function it_applies_overlap_between_consecutive_chunks(): void
    {
        $chunker = new DocumentChunker(chunkSize: 10, overlap: 3);
        $words = [];

        for ($i = 1; $i <= 25; $i++) {
            $words[] = 'w'.$i;
        }

        $chunks = $chunker->chunk(implode(' ', $words));

        $firstWords = explode(' ', $chunks[0]['content']);
        $secondWords = explode(' ', $chunks[1]['content']);

        $this->assertSame('w1', $firstWords[0]);
        $this->assertSame('w10', $firstWords[9]);
        $this->assertSame('w8', $secondWords[0]);
        $this->assertSame('w17', $secondWords[9]);
    }

    #[Test]
    public function it_returns_no_chunks_for_empty_or_whitespace_text(): void
    {
        $chunker = new DocumentChunker(chunkSize: 10, overlap: 2);

        $this->assertSame([], $chunker->chunk(''));
        $this->assertSame([], $chunker->chunk("   \n\n\t  "));
    }

    #[Test]
    public function it_creates_a_single_chunk_for_short_text(): void
    {
        $chunker = new DocumentChunker(chunkSize: 50, overlap: 10);

        $chunks = $chunker->chunk('Hello Laravel chunking');

        $this->assertCount(1, $chunks);
        $this->assertSame(0, $chunks[0]['chunk_index']);
        $this->assertSame('Hello Laravel chunking', $chunks[0]['content']);
        $this->assertNull($chunks[0]['token_count']);
        $this->assertSame(3, $chunks[0]['metadata']['word_count']);
    }

    #[Test]
    public function it_stores_expected_metadata(): void
    {
        $chunker = new DocumentChunker(chunkSize: 50, overlap: 10);
        $chunks = $chunker->chunk('Alpha beta gamma', 99);

        $this->assertSame([
            'source_document_id' => 99,
            'chunk_index' => 0,
            'character_count' => mb_strlen('Alpha beta gamma'),
            'word_count' => 3,
        ], $chunks[0]['metadata']);
    }

    #[Test]
    public function it_rejects_invalid_overlap_configuration(): void
    {
        $this->expectException(DocumentChunkingException::class);

        (new DocumentChunker(chunkSize: 10, overlap: 10))->chunk('one two three');
    }
}
