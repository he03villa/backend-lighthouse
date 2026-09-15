<?php

namespace App\Listeners;

use App\Events\ConversationCreated;
use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyConversationParticipants implements ShouldQueue
{
    public function handle(MessageSent|ConversationCreated|ConversationUpdated $event): void
    {
        try {
            $conversation = $event->conversation;

            $participantIds = $conversation->users()->pluck('users.id')->toArray();

            $users = User::whereIn('id', $participantIds)->get();

            foreach ($users as $user) {
                if (isset($event->message) && $event->message->sender_id === $user->id) {
                    continue;
                }

                broadcast()->to("user.{$user->id}", new ConversationUpdated($conversation));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify conversation participants', [
                'conversation_id' => $conversation->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
