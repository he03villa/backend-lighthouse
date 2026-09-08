<?php

namespace Database\Factories;

use App\Models\PlanningTask;
use App\Models\PlanningColumn;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanningTaskFactory extends Factory
{
    protected $model = PlanningTask::class;

    public function definition(): array
    {
        return [
            'column_id' => PlanningColumn::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'order' => fake()->numberBetween(1, 10),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function forColumn(PlanningColumn $column): static
    {
        return $this->state(fn () => ['column_id' => $column->id]);
    }

    public function highPriority(): static
    {
        return $this->state(fn () => ['priority' => 'high']);
    }
}
