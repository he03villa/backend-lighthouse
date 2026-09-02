<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    return Conversation::withoutGlobalScope('tenant')
        ->where('id', $conversationId)
        ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
        ->exists();
});
