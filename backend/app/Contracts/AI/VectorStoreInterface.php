<?php

namespace App\Contracts\AI;

interface VectorStoreInterface
{
    /**
     * Find the most semantically similar document chunks.
     *
     * @param  list<float|int>  $queryVector
     * @param  list<int>  $documentIds  Authorized document IDs only.
     * @return list<array{
     *     chunk_id: int,
     *     document_id: int,
     *     chunk_index: int,
     *     score: float,
     *     content: string
     * }>
     */
    public function search(
        array $queryVector,
        array $documentIds,
        int $limit,
        float $minScore = 0.0,
    ): array;
}
