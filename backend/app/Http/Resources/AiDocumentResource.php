<?php

namespace App\Http\Resources;

use App\Models\AiDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AiDocument
 */
class AiDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'status' => $this->status->value,
            'chunk_count' => (int) ($this->chunks_count ?? 0),
            'created_at' => $this->created_at,
            'processed_at' => $this->processed_at,
        ];
    }
}
