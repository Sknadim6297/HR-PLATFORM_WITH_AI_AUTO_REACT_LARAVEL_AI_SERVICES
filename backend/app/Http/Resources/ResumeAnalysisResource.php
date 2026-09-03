<?php

namespace App\Http\Resources;

use App\Models\AiResumeAnalysis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AiResumeAnalysis
 */
class ResumeAnalysisResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'summary' => $this->summary,
            'skills' => $this->skills ?? [],
            'experience' => $this->experience ?? [],
            'education' => $this->education ?? [],
            'strengths' => $this->strengths ?? [],
            'gaps' => $this->gaps ?? [],
            'confidence' => $this->confidence,
            'analyzed_at' => $this->analyzed_at,
        ];
    }
}
