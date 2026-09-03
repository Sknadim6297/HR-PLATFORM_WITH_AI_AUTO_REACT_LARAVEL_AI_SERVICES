<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiRagResponseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{answer: string, confidence: string, sources: list<array{chunk_id: int, document_id: int, chunk_index: int, score: float}>, conversation_id: int} $result */
        $result = $this->resource;

        return [
            'answer' => $result['answer'],
            'confidence' => $result['confidence'],
            'conversation_id' => $result['conversation_id'],
            'sources' => array_map(
                static fn (array $source): array => [
                    'chunk_id' => $source['chunk_id'],
                    'document_id' => $source['document_id'],
                    'chunk_index' => $source['chunk_index'],
                    'score' => $source['score'],
                ],
                $result['sources'],
            ),
        ];
    }
}
