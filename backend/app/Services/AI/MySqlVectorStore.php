<?php

namespace App\Services\AI;

use App\Contracts\AI\VectorStoreInterface;
use App\Models\AiDocumentChunk;
use Illuminate\Support\Facades\Log;

class MySqlVectorStore implements VectorStoreInterface
{
    public function __construct(
        private readonly CosineSimilarity $similarity,
    ) {}

    public function search(
        array $queryVector,
        array $documentIds,
        int $limit,
        float $minScore = 0.0,
    ): array {
        if ($documentIds === [] || $limit < 1 || $queryVector === []) {
            return [];
        }

        $startedAt = microtime(true);
        $scored = [];
        $skipped = 0;

        $chunks = AiDocumentChunk::query()
            ->select(['id', 'ai_document_id', 'chunk_index', 'content', 'embedding'])
            ->whereIn('ai_document_id', $documentIds)
            ->whereNotNull('embedding')
            ->whereNotNull('embedded_at')
            ->orderBy('id')
            ->get();

        foreach ($chunks as $chunk) {
            $embedding = $chunk->embedding;

            if (! is_array($embedding) || $embedding === []) {
                $skipped++;

                continue;
            }

            $score = $this->similarity->score($queryVector, $embedding);

            if ($score === null) {
                $skipped++;

                continue;
            }

            if ($score < $minScore) {
                continue;
            }

            $scored[] = [
                'chunk_id' => (int) $chunk->id,
                'document_id' => (int) $chunk->ai_document_id,
                'chunk_index' => (int) $chunk->chunk_index,
                'score' => round($score, 6),
                'content' => (string) $chunk->content,
            ];
        }

        usort(
            $scored,
            static fn (array $left, array $right): int => $right['score'] <=> $left['score']
        );

        $results = array_slice($scored, 0, $limit);

        Log::info('Vector search completed.', [
            'driver' => 'mysql',
            'document_count' => count($documentIds),
            'candidate_count' => $chunks->count(),
            'skipped_count' => $skipped,
            'result_count' => count($results),
            'top_k' => $limit,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        return array_values($results);
    }
}
