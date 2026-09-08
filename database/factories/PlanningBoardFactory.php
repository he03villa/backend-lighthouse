<?php

namespace Database\Factories;

use App\Models\PlanningBoard;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanningBoardFactory extends Factory
{
    protected $model = PlanningBoard::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->sentence(2),
            'description' => fake()->paragraph(),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn () => ['tenant_id' => $tenant->id]);
    }
}
