<?php

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\User;

class JobApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, JobApplication $application): bool
    {
        if ($user->isAdmin() || $user->isHr()) {
            return true;
        }

        return $application->candidate_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isCandidate();
    }

    public function updateStatus(User $user, JobApplication $application): bool
    {
        if ($user->isAdmin() || $user->isHr()) {
            return true;
        }

        return $user->isCandidate() && $application->candidate_id === $user->id;
    }

    public function screen(User $user, JobApplication $application): bool
    {
        return $user->isAdmin() || $user->isHr();
    }
}
