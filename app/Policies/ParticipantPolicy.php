<?php

namespace App\Policies;

use App\Models\Participant;
use App\Models\User;

class ParticipantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff']);
    }

    public function view(User $user, Participant $participant): bool
    {
        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return true;
        }

        if ($user->hasRole('parent')) {
            return $participant->guardians()->where('users.id', $user->id)->exists();
        }

        if ($user->hasRole('participant')) {
            return $participant->guardians()->where('users.id', $user->id)->exists()
                && $participant->guardians()->where('users.id', $user->id)->wherePivot('relationship', 'self')->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function update(User $user, Participant $participant): bool
    {
        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return true;
        }

        if ($user->hasRole('parent')) {
            return $participant->guardians()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    public function delete(User $user, Participant $participant): bool
    {
        return $user->hasAnyRole(['owner', 'admin']);
    }

    public function addGuardian(User $user, Participant $participant): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function removeGuardian(User $user, Participant $participant): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }
}
