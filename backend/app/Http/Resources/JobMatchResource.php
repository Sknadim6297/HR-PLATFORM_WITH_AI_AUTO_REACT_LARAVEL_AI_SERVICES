<?php

namespace App\Http\Resources;

use App\Models\AiJobMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AiJobMatch
 */
class JobMatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'score' => $this->score,
            'matched_skills' => $this->matched_skills ?? [],
            'missing_skills' => $this->missing_skills ?? [],
            'reasoning' => $this->reasoning,
            'confidence' => $this->confidence,
            'generated_at' => $this->generated_at,
        ];
    }
}
