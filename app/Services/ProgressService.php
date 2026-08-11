<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Enrollment;
use App\Models\ProgressRecord;

class ProgressService
{
    public function initializeFor(Enrollment $enrollment): ProgressRecord
    {
        $attributes = [
            'tenant_id' => $enrollment->tenant_id,
            'program_id' => $enrollment->program_id,
            'participant_id' => $enrollment->participant_id,
            'percentage' => 0,
            'completed_activities' => 0,
            'total_activities' => $this->totalActivities($enrollment->program_id),
        ];

        return ProgressRecord::withoutGlobalScope('tenant')->updateOrCreate(
            ['enrollment_id' => $enrollment->id],
            $attributes,
        );
    }

    public function recalculate(Enrollment $enrollment): ProgressRecord
    {
        $total = $this->totalActivities($enrollment->program_id);

        $completed = ActivitySubmission::withoutGlobalScope('tenant')
            ->where('enrollment_id', $enrollment->id)
            ->where('status', SubmissionStatus::Approved)
            ->count();

        $percentage = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

        return ProgressRecord::withoutGlobalScope('tenant')->updateOrCreate(
            ['enrollment_id' => $enrollment->id],
            [
                'tenant_id' => $enrollment->tenant_id,
                'program_id' => $enrollment->program_id,
                'participant_id' => $enrollment->participant_id,
                'percentage' => $percentage,
                'completed_activities' => $completed,
                'total_activities' => $total,
            ],
        );
    }

    protected function totalActivities(string $programId): int
    {
        return Activity::withoutGlobalScope('tenant')
            ->join('modules', 'modules.id', '=', 'activities.module_id')
            ->where('modules.program_id', $programId)
            ->count();
    }
}
