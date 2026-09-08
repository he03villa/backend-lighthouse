<?php

namespace App\Policies;

use App\Models\ForumPost;
use App\Models\User;

class ForumPostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff', 'parent', 'participant']);
    }

    public function view(User $user, ForumPost $post): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff', 'parent', 'participant']);
    }

    public function update(User $user, ForumPost $post): bool
    {
        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return true;
        }

        return $post->author_user_id === $user->id;
    }

    public function delete(User $user, ForumPost $post): bool
    {
        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return true;
        }

        return $post->author_user_id === $user->id;
    }

    public function moderate(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach']);
    }

    public function toggleReaction(User $user, ForumPost $post): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff', 'parent', 'participant']);
    }
}
