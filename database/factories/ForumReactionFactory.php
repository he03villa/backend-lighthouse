<?php

namespace Database\Factories;

use App\Models\ForumReaction;
use App\Models\ForumPost;
use App\Models\ForumComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumReactionFactory extends Factory
{
    protected $model = ForumReaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => ForumPost::factory(),
            'comment_id' => null,
            'type' => fake()->randomElement(['like', 'love', 'helpful', 'insightful']),
        ];
    }

    public function forPost(ForumPost $post): static
    {
        return $this->state(fn () => ['post_id' => $post->id, 'comment_id' => null]);
    }

    public function forComment(ForumComment $comment): static
    {
        return $this->state(fn () => ['comment_id' => $comment->id, 'post_id' => null]);
    }
}
