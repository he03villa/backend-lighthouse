<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Events\EnrollmentCreated;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentService
{
    public function list(?string $participantId = null, ?User $user = null): Collection
    {
        $query = Enrollment::query()->with('participant', 'program', 'progressRecord');

        if ($user && $user->hasRole('parent')) {
            $participantIds = $user->guardianships()->pluck('participants.id');
            $query->whereIn('participant_id', $participantIds);
        }

        if ($participantId) {
            $query->where('participant_id', $participantId);
        }

        return $query->get();
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

        EnrollmentCreated::dispatch($enrollment);

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
