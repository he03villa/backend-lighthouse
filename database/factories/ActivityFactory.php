<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Module;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'tenant_id' => fn (array $attributes) => Module::find($attributes['module_id'])?->tenant_id ?? Tenant::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'objective' => fake()->sentence(),
            'type' => fake()->randomElement(['exercise', 'assessment', 'project', 'quiz']),
            'level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'order' => fake()->numberBetween(1, 10),
        ];
    }

    public function forModule(Module $module): static
    {
        return $this->state(fn () => [
            'module_id' => $module->id,
            'tenant_id' => $module->tenant_id,
        ]);
    }
}
