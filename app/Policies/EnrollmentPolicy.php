<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff', 'parent']);
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return true;
        }

        if ($user->hasRole('parent')) {
            return $enrollment->participant->guardians()->where('users.id', $user->id)->exists();
        }

        if ($user->hasRole('participant')) {
            return $enrollment->participant->guardians()->where('users.id', $user->id)->exists()
                && $enrollment->participant->guardians()->where('users.id', $user->id)->wherePivot('relationship', 'self')->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function viewProgress(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return true;
        }

        if ($user->hasRole('parent')) {
            return $enrollment->participant->guardians()->where('users.id', $user->id)->exists();
        }

        if ($user->hasRole('participant')) {
            return $enrollment->participant->guardians()->where('users.id', $user->id)->exists()
                && $enrollment->participant->guardians()->where('users.id', $user->id)->wherePivot('relationship', 'self')->exists();
        }

        return false;
    }
}
