<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Enrollment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivitySubmissionFactory extends Factory
{
    protected $model = ActivitySubmission::class;

    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'activity_id' => Activity::factory(),
            'tenant_id' => fn (array $attributes) => Enrollment::find($attributes['enrollment_id'])?->tenant_id ?? Tenant::factory(),
            'status' => 'pending',
            'submitted_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'reviewed_at' => null,
            'reviewed_by' => null,
            'feedback' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved', 'reviewed_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected', 'reviewed_at' => now()]);
    }
}
