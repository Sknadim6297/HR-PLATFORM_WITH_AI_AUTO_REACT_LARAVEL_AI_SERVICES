<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiSearchResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{chunk_id: int, document_id: int, chunk_index: int, score: float, content: string} $result */
        $result = $this->resource;

        return [
            'chunk_id' => $result['chunk_id'],
            'document_id' => $result['document_id'],
            'chunk_index' => $result['chunk_index'],
            'score' => $result['score'],
            'content' => $result['content'],
        ];
    }
}
