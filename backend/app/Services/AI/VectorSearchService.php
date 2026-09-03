<?php

namespace App\Services\AI;

use App\Contracts\AI\VectorStoreInterface;
use App\Enums\AiDocumentStatus;
use App\Exceptions\EmbeddingException;
use App\Models\User;
use InvalidArgumentException;

class VectorSearchService
{
    public function __construct(
        private readonly VectorStoreInterface $vectorStore,
    ) {}

    /**
     * Search authorized completed document chunks for the given user.
     *
     * @param  list<float|int>  $queryVector
     * @return list<array{
     *     chunk_id: int,
     *     document_id: int,
     *     chunk_index: int,
     *     score: float,
     *     content: string
     * }>|null  Null means the requested document was not found for this user.
     */
    public function searchForUser(
        User $user,
        array $queryVector,
        ?int $documentId = null,
        ?int $topK = null,
        ?float $minScore = null,
    ): ?array {
        $this->assertValidQueryVector($queryVector);

        $limit = $this->resolveTopK($topK);
        $threshold = $this->resolveMinScore($minScore);

        if ($documentId !== null) {
            $owned = $user->aiDocuments()->whereKey($documentId)->first();

            if ($owned === null) {
                return null;
            }

            if ($owned->status !== AiDocumentStatus::Completed) {
                return [];
            }

            $documentIds = [(int) $owned->id];
        } else {
            $documentIds = $user->aiDocuments()
                ->where('status', AiDocumentStatus::Completed)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        if ($documentIds === []) {
            return [];
        }

        return $this->vectorStore->search(
            $queryVector,
            $documentIds,
            $limit,
            $threshold,
        );
    }

    /**
     * @param  list<float|int>  $queryVector
     */
    private function assertValidQueryVector(array $queryVector): void
    {
        if ($queryVector === []) {
            throw new EmbeddingException(
                'Query embedding vector cannot be empty.',
                retryable: false,
            );
        }

        foreach ($queryVector as $value) {
            if (! is_int($value) && ! is_float($value)) {
                throw new EmbeddingException(
                    'Query embedding vector contains invalid values.',
                    retryable: false,
                );
            }
        }
    }

    private function resolveTopK(?int $topK): int
    {
        $resolved = $topK ?? (int) config('ai.vector_search.top_k', 5);

        if ($resolved < 1 || $resolved > 20) {
            throw new InvalidArgumentException('top_k must be between 1 and 20.');
        }

        return $resolved;
    }

    private function resolveMinScore(?float $minScore): float
    {
        $resolved = $minScore ?? (float) config('ai.vector_search.min_score', 0.30);

        if ($resolved < -1.0 || $resolved > 1.0) {
            throw new InvalidArgumentException('min_score must be between -1 and 1.');
        }

        return $resolved;
    }
}
