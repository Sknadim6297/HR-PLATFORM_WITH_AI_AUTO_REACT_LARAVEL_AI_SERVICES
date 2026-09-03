<?php

namespace App\Policies;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Job $job): bool
    {
        if ($user->isAdmin() || $user->isHr()) {
            return true;
        }

        return $job->status === JobStatus::Published;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isHr();
    }

    public function update(User $user, Job $job): bool
    {
        return $user->isAdmin() || $user->isHr();
    }

    public function delete(User $user, Job $job): bool
    {
        return $user->isAdmin() || $user->isHr();
    }

    public function publish(User $user, Job $job): bool
    {
        return $user->isAdmin() || $user->isHr();
    }

    public function close(User $user, Job $job): bool
    {
        return $user->isAdmin() || $user->isHr();
    }

    public function viewApplications(User $user, Job $job): bool
    {
        return $user->isAdmin() || $user->isHr();
    }
}
