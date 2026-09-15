<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Program;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModuleFactory extends Factory
{
    protected $model = Module::class;

    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'tenant_id' => fn (array $attributes) => Program::find($attributes['program_id'])?->tenant_id ?? Tenant::factory(),
            'name' => fake()->sentence(2),
            'description' => fake()->paragraph(),
            'order' => fake()->numberBetween(1, 10),
        ];
    }

    public function forProgram(Program $program): static
    {
        return $this->state(fn () => [
            'program_id' => $program->id,
            'tenant_id' => $program->tenant_id,
        ]);
    }
}
