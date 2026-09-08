<?php

namespace App\Policies;

use App\Models\PlanningBoard;
use App\Models\User;

class PlanningBoardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff']);
    }

    public function view(User $user, PlanningBoard $board): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function update(User $user, PlanningBoard $board): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function delete(User $user, PlanningBoard $board): bool
    {
        return $user->hasAnyRole(['owner', 'admin']);
    }
}
