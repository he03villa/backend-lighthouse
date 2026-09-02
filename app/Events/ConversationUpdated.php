<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public string $type,
        public ?array $lastMessage = null,
        public ?int $unreadCount = null,
    ) {}

    public function broadcastOn(): array
    {
        return $this->conversation->participants
            ->map(fn ($user) => new PrivateChannel('user.'.$user->id))
            ->values()
            ->toArray();
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->conversation->id,
            'type' => $this->type,
            'last_message' => $this->lastMessage,
            'unread_count' => $this->unreadCount,
        ];
    }
}
