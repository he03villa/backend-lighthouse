<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'tenant_id' => \App\Models\Tenant::factory(),
            'author_id' => User::factory(),
            'participant_id' => \App\Models\Participant::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'privacy' => 'private',
            'mood' => fake()->randomElement(['happy', 'neutral', 'sad', 'excited', 'anxious']),
        ];
    }

    public function private(): static
    {
        return $this->state(fn () => ['privacy' => 'private']);
    }

    public function shared(): static
    {
        return $this->state(fn () => ['privacy' => 'shared']);
    }
}
