<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'participant_id' => Participant::factory(),
            'program_id' => Program::factory(),
            'status' => 'active',
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn () => ['tenant_id' => $tenant->id]);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
    }

    public function dropped(): static
    {
        return $this->state(fn () => ['status' => 'dropped']);
    }
}
