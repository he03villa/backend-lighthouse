<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
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
        return 'conversation.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->conversation->id,
            'is_group' => $this->conversation->is_group,
            'title' => $this->conversation->title,
            'created_at' => $this->conversation->created_at?->toISOString(),
            'participants' => $this->conversation->participants->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->values(),
            'latest_message' => [],
        ];
    }
}
