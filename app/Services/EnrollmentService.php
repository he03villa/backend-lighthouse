<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentService
{
    public function list(): Collection
    {
        return Enrollment::query()->with('participant', 'program', 'progressRecord')->get();
    }

    public function create(array $data): Enrollment
    {
        $enrollment = Enrollment::create([
            'participant_id' => $data['participant_id'],
            'program_id' => $data['program_id'],
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        app(ProgressService::class)->initializeFor($enrollment);

        return $enrollment->load('participant', 'program', 'progressRecord');
    }

    public function delete(Enrollment $enrollment): void
    {
        $enrollment->update([
            'status' => EnrollmentStatus::Dropped,
            'completed_at' => now(),
        ]);
    }
}
