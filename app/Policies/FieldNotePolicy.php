<?php

namespace App\Policies;

use App\Models\FieldNote;
use App\Models\User;

class FieldNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff']);
    }

    public function view(User $user, FieldNote $fieldNote): bool
    {
        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return true;
        }

        if ($user->hasRole('parent')) {
            return $fieldNote->participant->guardians()->where('users.id', $user->id)->exists();
        }

        if ($user->hasRole('participant')) {
            return $fieldNote->author_user_id === $user->id
                || ($fieldNote->participant->guardians()->where('users.id', $user->id)->exists()
                    && $fieldNote->participant->guardians()->where('users.id', $user->id)->wherePivot('relationship', 'self')->exists());
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function update(User $user, FieldNote $fieldNote): bool
    {
        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return true;
        }

        return $fieldNote->author_user_id === $user->id;
    }

    public function delete(User $user, FieldNote $fieldNote): bool
    {
        if ($user->hasAnyRole(['owner', 'admin'])) {
            return true;
        }

        return $fieldNote->author_user_id === $user->id;
    }
}
