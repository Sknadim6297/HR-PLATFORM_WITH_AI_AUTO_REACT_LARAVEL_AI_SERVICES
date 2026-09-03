<?php

namespace App\Services\AI;

class RagContextBuilder
{
    /**
     * @param  list<array{chunk_id: int, document_id: int, chunk_index: int, score: float, content: string}>  $chunks
     */
    public function build(array $chunks): string
    {
        $maxChunks = max(1, (int) config('ai.rag.max_context_chunks', 5));
        $maxCharacters = max(500, (int) config('ai.rag.max_context_characters', 12000));

        $sections = [];
        $usedCharacters = 0;

        foreach (array_slice($chunks, 0, $maxChunks) as $chunk) {
            $content = trim((string) ($chunk['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            $section = sprintf(
                "DOCUMENT %d\nCHUNK %d\nRELEVANCE: %.4f\n\n%s",
                (int) $chunk['document_id'],
                (int) $chunk['chunk_index'],
                (float) $chunk['score'],
                $content,
            );

            $projected = $usedCharacters + mb_strlen($section) + ($sections === [] ? 0 : 5);

            if ($sections !== [] && $projected > $maxCharacters) {
                break;
            }

            if (mb_strlen($section) > $maxCharacters && $sections === []) {
                $section = mb_substr($section, 0, $maxCharacters);
            }

            $sections[] = $section;
            $usedCharacters += mb_strlen($section) + 5;
        }

        return implode("\n\n---\n\n", $sections);
    }
}
