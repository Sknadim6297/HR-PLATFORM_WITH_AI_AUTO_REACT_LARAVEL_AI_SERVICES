<?php

namespace App\Http\Resources;

use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobApplication
 */
class JobApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'candidate_id' => $this->candidate_id,
            'resume_document_id' => $this->resume_document_id,
            'cover_letter' => $this->cover_letter,
            'status' => $this->status?->value,
            'applied_at' => $this->applied_at,
            'job' => new JobResource($this->whenLoaded('job')),
            'resume_document' => new AiDocumentResource($this->whenLoaded('resumeDocument')),
            'candidate' => $this->whenLoaded('candidate', function () {
                return [
                    'id' => $this->candidate->id,
                    'name' => $this->candidate->name,
                    'email' => $this->candidate->email,
                    'profile' => $this->candidate->relationLoaded('candidateProfile')
                        ? new CandidateProfileResource($this->candidate->candidateProfile)
                        : null,
                ];
            }),
            'resume_analysis' => new ResumeAnalysisResource($this->whenLoaded('resumeAnalysis')),
            'job_match' => new JobMatchResource($this->whenLoaded('jobMatch')),
            'ai_screening' => $this->whenLoaded('aiScreeningAssessment', function () {
                return [
                    'id' => $this->aiScreeningAssessment->id,
                    'recommendation' => $this->aiScreeningAssessment->recommendation,
                    'score' => $this->aiScreeningAssessment->score,
                    'reasoning' => $this->aiScreeningAssessment->reasoning,
                    'confidence' => $this->aiScreeningAssessment->confidence,
                    'screened_at' => $this->aiScreeningAssessment->screened_at,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
