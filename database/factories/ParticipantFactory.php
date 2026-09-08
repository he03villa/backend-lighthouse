<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-18 years', '-5 years'),
            'avatar' => null,
            'metadata' => [],
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn () => ['tenant_id' => $tenant->id]);
    }

    public function underage(): static
    {
        return $this->state(fn () => ['birth_date' => fake()->dateTimeBetween('-13 years', '-5 years')]);
    }
}
