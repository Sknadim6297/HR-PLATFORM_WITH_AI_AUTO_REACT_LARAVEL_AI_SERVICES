<?php

namespace App\Services\AI;

final class CosineSimilarity
{
    /**
     * Compute cosine similarity between two vectors.
     *
     * Returns null when vectors are empty, zero-magnitude, or dimension-mismatched.
     *
     * @param  list<float|int>  $a
     * @param  list<float|int>  $b
     */
    public function score(array $a, array $b): ?float
    {
        if ($a === [] || $b === []) {
            return null;
        }

        if (count($a) !== count($b)) {
            return null;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $index => $valueA) {
            if (! is_int($valueA) && ! is_float($valueA)) {
                return null;
            }

            $valueB = $b[$index];

            if (! is_int($valueB) && ! is_float($valueB)) {
                return null;
            }

            $dot += (float) $valueA * (float) $valueB;
            $normA += (float) $valueA * (float) $valueA;
            $normB += (float) $valueB * (float) $valueB;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return null;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
