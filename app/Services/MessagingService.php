<?php

namespace App\Services;

use App\Events\ConversationCreated;
use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MessagingService
{
    public function __construct(
        protected TenantContext $tenantContext,
    ) {}

    public function listConversations(User $user): Collection
    {
        return Conversation::query()
            ->where('tenant_id', $this->tenantContext->id())
            ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->with('participants')
            ->withLatestMessage()
            ->latest()
            ->get();
    }

    public function showConversation(User $user, Conversation $conversation): Conversation
    {
        $this->assertParticipant($user, $conversation);

        return $conversation->load('participants');
    }

    public function createConversation(User $creator, array $userIds, ?string $title = null): Conversation
    {
        $allUserIds = array_unique(array_merge([$creator->id], $userIds));

        return DB::transaction(function () use ($allUserIds, $title) {
            $conversation = Conversation::create([
                'tenant_id' => $this->tenantContext->id(),
                'is_group' => count($allUserIds) > 2,
                'title' => $title,
            ]);

            $conversation->participants()->sync($allUserIds);
            $conversation->load('participants');

            ConversationCreated::dispatch($conversation);

            return $conversation;
        });
    }

    public function sendMessage(User $sender, Conversation $conversation, string $content, ?array $media = null): Message
    {
        $this->assertParticipant($sender, $conversation);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_user_id' => $sender->id,
            'content' => $content,
            'media' => $media,
        ]);

        MessageSent::dispatch($message->load('sender'));

        $conversation->load('participants');
        $conversation->participants
            ->filter(fn ($user) => $user->id !== $sender->id)
            ->each(function ($participant) use ($conversation, $message) {
                $lastReadAt = DB::table('conversation_participants')
                    ->where('conversation_id', $conversation->id)
                    ->where('user_id', $participant->id)
                    ->value('last_read_at');

                $unreadCount = Message::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('sender_user_id', '!=', $participant->id)
                    ->where(function ($q) use ($lastReadAt) {
                        if ($lastReadAt) {
                            $q->where('created_at', '>', $lastReadAt);
                        }
                    })
                    ->count();

                ConversationUpdated::dispatch(
                    $conversation,
                    type: 'last_message',
                    lastMessage: [
                        'id' => $message->id,
                        'content' => $message->content,
                        'sender_user_id' => $message->sender_user_id,
                        'conversation_id' => $message->conversation_id,
                        'created_at' => $message->created_at->toISOString(),
                        'sender' => $message->sender
                            ? ['id' => $message->sender->id, 'name' => $message->sender->name]
                            : null,
                    ],
                    unreadCount: $unreadCount,
                );
            });

        return $message->load('sender');
    }

    public function getMessages(Conversation $conversation, int $page = 1, int $perPage = 50): LengthAwarePaginator
    {
        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->with('sender')
            ->oldest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function markAsRead(User $user, Conversation $conversation): void
    {
        $this->assertParticipant($user, $conversation);

        DB::table('conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now(), 'updated_at' => now()]);

        $conversation->load('participants');
        $conversation->participants
            ->filter(fn ($u) => $u->id !== $user->id)
            ->each(fn ($participant) => ConversationUpdated::dispatch(
                $conversation,
                type: 'read',
            ));
    }

    protected function assertParticipant(User $user, Conversation $conversation): void
    {
        abort_unless(
            $conversation->participants()->where('users.id', $user->id)->exists(),
            403,
            'You are not a participant of this conversation.',
        );
    }
}
