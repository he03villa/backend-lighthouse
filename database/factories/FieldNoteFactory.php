<?php

namespace Database\Factories;

use App\Models\FieldNote;
use App\Models\Participant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FieldNoteFactory extends Factory
{
    protected $model = FieldNote::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'author_id' => User::factory(),
            'participant_id' => Participant::factory(),
            'submission_id' => null,
            'content' => fake()->paragraph(),
            'visibility' => 'private',
        ];
    }

    public function public(): static
    {
        return $this->state(fn () => ['visibility' => 'public']);
    }

    public function team(): static
    {
        return $this->state(fn () => ['visibility' => 'team']);
    }
}
