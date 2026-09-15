<?php

namespace App\Policies;

use App\Models\ActivitySubmission;
use App\Models\User;

class ActivitySubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff', 'parent']);
    }

    public function view(User $user, ActivitySubmission $submission): bool
    {
        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return true;
        }

        if ($user->hasRole('parent')) {
            return $submission->enrollment->participant->guardians()->where('users.id', $user->id)->exists();
        }

        if ($user->hasRole('participant')) {
            return $submission->enrollment->participant->guardians()->where('users.id', $user->id)->exists()
                && $submission->enrollment->participant->guardians()->where('users.id', $user->id)->wherePivot('relationship', 'self')->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('participant')) {
            return true;
        }

        if ($user->hasRole('parent')) {
            return true;
        }

        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function review(User $user, ActivitySubmission $submission): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }
}
