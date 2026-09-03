<?php

namespace App\Policies;

use App\Models\CandidateProfile;
use App\Models\User;

class CandidateProfilePolicy
{
    public function view(User $user, CandidateProfile $profile): bool
    {
        if ($user->isAdmin() || $user->isHr()) {
            return true;
        }

        return $profile->user_id === $user->id;
    }

    public function update(User $user, CandidateProfile $profile): bool
    {
        return $profile->user_id === $user->id && $user->isCandidate();
    }

    public function create(User $user): bool
    {
        return $user->isCandidate();
    }
}
