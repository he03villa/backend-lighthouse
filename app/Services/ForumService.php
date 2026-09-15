<?php

namespace App\Services;

use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Models\ForumReaction;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Pagination\LengthAwarePaginator;

class ForumService
{
    public function __construct(
        protected TenantContext $tenantContext,
    ) {}

    public function listPosts(?string $category = null, ?User $user = null): LengthAwarePaginator
    {
        $posts = ForumPost::query()
            ->where('tenant_id', $this->tenantContext->id())
            ->with('author')
            ->withCount('comments')
            ->withCount('reactions')
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderByDesc('pinned')
            ->latest()
            ->paginate(20);

        if ($user) {
            $postIds = $posts->pluck('id');
            $userReactions = ForumReaction::whereIn('post_id', $postIds)
                ->where('user_id', $user->id)
                ->pluck('type', 'post_id');

            $posts->getCollection()->transform(function ($post) use ($userReactions) {
                $post->user_reaction = $userReactions->get($post->id);

                return $post;
            });
        }

        return $posts;
    }

    public function showPost(ForumPost $post, ?User $user = null): ForumPost
    {
        $post->load('author', 'comments.author', 'comments.reactions', 'comments.reactions.user', 'reactions', 'reactions.user');

        if ($user) {
            $post->user_reaction = $post->reactions->firstWhere('user_id', $user->id)?->type;
            foreach ($post->comments as $comment) {
                $comment->user_reaction = $comment->reactions->firstWhere('user_id', $user->id)?->type;
            }
        }

        return $post;
    }

    public function createPost(User $author, array $data): ForumPost
    {
        return ForumPost::create([
            'tenant_id' => $this->tenantContext->id(),
            'author_user_id' => $author->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'category' => $data['category'] ?? null,
        ])->load('author');
    }

    public function updatePost(User $user, ForumPost $post, array $data): ForumPost
    {
        $post->update([
            'title' => array_key_exists('title', $data) ? $data['title'] : $post->title,
            'content' => array_key_exists('content', $data) ? $data['content'] : $post->content,
            'category' => array_key_exists('category', $data) ? $data['category'] : $post->category,
            'pinned' => array_key_exists('pinned', $data) ? $data['pinned'] : $post->pinned,
        ]);

        return $post->load('author');
    }

    public function deletePost(User $user, ForumPost $post): void
    {
        $post->delete();
    }

    public function createComment(User $author, ForumPost $post, string $content): ForumComment
    {
        return ForumComment::create([
            'post_id' => $post->id,
            'author_user_id' => $author->id,
            'content' => $content,
        ])->load('author');
    }

    public function deleteComment(User $user, ForumComment $comment): void
    {
        abort_unless(
            $comment->author_user_id === $user->id || $user->hasRole(['owner', 'admin']),
            403,
            'You can only delete your own comments.',
        );

        $comment->delete();
    }

    public function toggleReaction(User $user, ForumPost|ForumComment $target, string $type): bool
    {
        $isPost = $target instanceof ForumPost;

        $existing = ForumReaction::query()
            ->where('user_id', $user->id)
            ->when($isPost, fn ($q) => $q->where('post_id', $target->id))
            ->when(! $isPost, fn ($q) => $q->where('comment_id', $target->id))
            ->first();

        if ($existing) {
            if ($existing->type === $type) {
                $existing->delete();

                return false;
            }

            $existing->update(['type' => $type]);

            return true;
        }

        ForumReaction::create([
            'user_id' => $user->id,
            'post_id' => $isPost ? $target->id : null,
            'comment_id' => $isPost ? null : $target->id,
            'type' => $type,
        ]);

        return true;
    }

    protected function assertCanModerate(User $user, ForumPost $post): void
    {
        $isAuthor = $post->author_user_id === $user->id;
        $isModerator = $user->hasRole(['owner', 'admin']);

        abort_unless($isAuthor || $isModerator, 403, 'You can only edit/delete your own posts.');
    }
}
