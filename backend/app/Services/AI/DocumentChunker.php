<?php

namespace App\Services\AI;

use App\Exceptions\DocumentChunkingException;

class DocumentChunker
{
    public function __construct(
        private readonly ?int $chunkSize = null,
        private readonly ?int $overlap = null,
    ) {}

    /**
     * Split normalized text into ordered word-based chunks with overlap.
     *
     * @return list<array{
     *     chunk_index: int,
     *     content: string,
     *     token_count: null,
     *     metadata: array{source_document_id: int|null, chunk_index: int, character_count: int, word_count: int}
     * }>
     */
    public function chunk(string $text, ?int $documentId = null): array
    {
        $chunkSize = $this->chunkSize ?? (int) config('ai.document_chunking.chunk_size', 700);
        $overlap = $this->overlap ?? (int) config('ai.document_chunking.overlap', 100);

        $this->validateConfiguration($chunkSize, $overlap);

        $normalized = $this->normalize($text);

        if ($normalized === '') {
            return [];
        }

        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false || $words === []) {
            return [];
        }

        $totalWords = count($words);

        if ($totalWords <= $chunkSize) {
            return [$this->makeChunk(0, $words, $documentId)];
        }

        $step = $chunkSize - $overlap;
        $chunks = [];
        $chunkIndex = 0;
        $start = 0;

        while ($start < $totalWords) {
            $slice = array_slice($words, $start, $chunkSize);

            if ($slice === []) {
                break;
            }

            $chunks[] = $this->makeChunk($chunkIndex, $slice, $documentId);
            $chunkIndex++;

            $nextStart = $start + $step;

            // Final partial window: avoid a tiny trailing duplicate caused by overlap.
            if ($nextStart < $totalWords && ($totalWords - $nextStart) < $overlap) {
                break;
            }

            $start = $nextStart;
        }

        return $chunks;
    }

    /**
     * @param  list<string>  $words
     * @return array{
     *     chunk_index: int,
     *     content: string,
     *     token_count: null,
     *     metadata: array{source_document_id: int|null, chunk_index: int, character_count: int, word_count: int}
     * }
     */
    private function makeChunk(int $chunkIndex, array $words, ?int $documentId): array
    {
        $content = implode(' ', $words);

        return [
            'chunk_index' => $chunkIndex,
            'content' => $content,
            // No local tokenizer is configured; do not treat word count as token count.
            'token_count' => null,
            'metadata' => [
                'source_document_id' => $documentId,
                'chunk_index' => $chunkIndex,
                'character_count' => mb_strlen($content),
                'word_count' => count($words),
            ],
        ];
    }

    private function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;
        $text = preg_replace('/ *\n */u', "\n", $text) ?? $text;

        return trim($text);
    }

    private function validateConfiguration(int $chunkSize, int $overlap): void
    {
        if ($chunkSize < 1) {
            throw new DocumentChunkingException(
                'Document chunk size must be at least 1 word.'
            );
        }

        if ($overlap < 0) {
            throw new DocumentChunkingException(
                'Document chunk overlap cannot be negative.'
            );
        }

        if ($overlap >= $chunkSize) {
            throw new DocumentChunkingException(
                'Document chunk overlap must be smaller than the chunk size.'
            );
        }
    }
}
