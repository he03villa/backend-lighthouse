<?php

namespace App\Policies;

use App\Models\ForumComment;
use App\Models\User;

class ForumCommentPolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff', 'parent', 'participant']);
    }

    public function delete(User $user, ForumComment $comment): bool
    {
        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return true;
        }

        return $comment->author_user_id === $user->id;
    }
}
