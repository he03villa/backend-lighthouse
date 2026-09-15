<?php

namespace Database\Factories;

use App\Models\PlanningBoard;
use App\Models\PlanningColumn;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanningColumnFactory extends Factory
{
    protected $model = PlanningColumn::class;

    public function definition(): array
    {
        return [
            'board_id' => PlanningBoard::factory(),
            'name' => fake()->word(),
            'order' => fake()->numberBetween(1, 10),
        ];
    }

    public function forBoard(PlanningBoard $board): static
    {
        return $this->state(fn () => ['board_id' => $board->id]);
    }
}
