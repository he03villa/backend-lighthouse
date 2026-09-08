<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff']);
    }

    public function view(User $user, Group $group): bool
    {
        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return true;
        }

        if ($user->hasRole('participant')) {
            return $group->participants()->whereHas('guardians', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function update(User $user, Group $group): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->hasAnyRole(['owner', 'admin']);
    }

    public function manageMembers(User $user, Group $group): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }
}
