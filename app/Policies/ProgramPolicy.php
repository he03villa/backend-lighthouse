<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff', 'parent', 'participant']);
    }

    public function view(User $user, Program $program): bool
    {
        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return true;
        }

        if ($user->hasRole('staff')) {
            return true;
        }

        if ($user->hasAnyRole(['parent', 'participant'])) {
            return $program->status === 'published';
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function update(User $user, Program $program): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function delete(User $user, Program $program): bool
    {
        return $user->hasAnyRole(['owner', 'admin']);
    }

    public function publish(User $user, Program $program): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function uploadThumbnail(User $user, Program $program): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }
}
