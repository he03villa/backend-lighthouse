<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff']);
    }

    public function view(User $user, JournalEntry $journal): bool
    {
        if ($user->hasAnyRole(['owner', 'admin'])) {
            return true;
        }

        if ($user->hasRole('coach')) {
            return $journal->participant->guardians()->where('users.id', $user->id)->exists()
                || $journal->participant->enrollments()->whereHas('program', function ($q) {
                    $q->where('tenant_id', $journal->tenant_id);
                })->exists();
        }

        if ($user->hasRole('parent')) {
            $isGuardian = $journal->participant->guardians()->where('users.id', $user->id)->exists();

            if ($isGuardian && in_array($journal->visibility->value, ['shared_family', 'public'])) {
                return true;
            }

            return false;
        }

        if ($user->hasRole('participant')) {
            return $journal->author_user_id === $user->id
                || in_array($journal->visibility->value, ['shared_participant', 'public']);
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return true;
        }

        if ($user->hasRole('participant')) {
            return true;
        }

        return false;
    }

    public function update(User $user, JournalEntry $journal): bool
    {
        if ($user->hasAnyRole(['owner', 'admin'])) {
            return true;
        }

        return $journal->author_user_id === $user->id;
    }

    public function delete(User $user, JournalEntry $journal): bool
    {
        if ($user->hasAnyRole(['owner', 'admin'])) {
            return true;
        }

        return $journal->author_user_id === $user->id;
    }
}
