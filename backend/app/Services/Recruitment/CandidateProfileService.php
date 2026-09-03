<?php

namespace App\Services\Recruitment;

use App\Models\CandidateProfile;
use App\Models\User;
use App\Services\Audit\AuditLogger;

class CandidateProfileService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(User $candidate, array $data): CandidateProfile
    {
        $profile = $candidate->candidateProfile;

        if ($profile === null) {
            $profile = CandidateProfile::query()->create([
                ...$data,
                'user_id' => $candidate->id,
            ]);

            $this->auditLogger->log($candidate, $profile, 'candidate_profile.created', null, [
                'headline' => $profile->headline,
            ]);
        } else {
            $old = $profile->only([
                'phone', 'location', 'headline', 'years_of_experience',
                'current_company', 'current_role', 'education_summary', 'skills',
            ]);

            $profile->fill($data)->save();

            $this->auditLogger->log($candidate, $profile, 'candidate_profile.updated', $old, $profile->only(array_keys($old)));
        }

        return $profile->fresh(['user:id,name,email']) ?? $profile;
    }
}
