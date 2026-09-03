<?php

namespace App\Http\Resources;

use App\Models\CandidateProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CandidateProfile
 */
class CandidateProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'phone' => $this->phone,
            'location' => $this->location,
            'headline' => $this->headline,
            'years_of_experience' => $this->years_of_experience,
            'current_company' => $this->current_company,
            'current_role' => $this->current_role,
            'education_summary' => $this->education_summary,
            'skills' => $this->skills ?? [],
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'updated_at' => $this->updated_at,
        ];
    }
}
