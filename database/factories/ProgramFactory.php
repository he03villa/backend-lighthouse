<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => 'draft',
            'thumbnail' => null,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn () => ['tenant_id' => $tenant->id]);
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => 'published']);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }
}
