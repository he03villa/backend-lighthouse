<?php

namespace Database\Factories;

use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumCommentFactory extends Factory
{
    protected $model = ForumComment::class;

    public function definition(): array
    {
        return [
            'post_id' => ForumPost::factory(),
            'author_id' => User::factory(),
            'content' => fake()->paragraph(),
        ];
    }

    public function forPost(ForumPost $post): static
    {
        return $this->state(fn () => ['post_id' => $post->id]);
    }
}
