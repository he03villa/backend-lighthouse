<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff', 'parent', 'participant']);
    }

    public function view(User $user, Message $message): bool
    {
        return $message->conversation->users()->where('users.id', $user->id)->exists()
            || $user->hasAnyRole(['owner', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'coach', 'staff', 'parent', 'participant']);
    }

    public function send(User $user, Message $message): bool
    {
        return $message->conversation->users()->where('users.id', $user->id)->exists();
    }

    public function markAsRead(User $user, Message $message): bool
    {
        return $message->conversation->users()->where('users.id', $user->id)->exists();
    }
}
