<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\EvidenceType;
use App\Enums\FieldNoteVisibility;
use App\Enums\SubmissionStatus;
use App\Events\SubmissionReviewed;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Enrollment;
use App\Models\Evidence;
use App\Models\FieldNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SubmissionService
{
    public function list(array $filters = [], ?User $user = null): Collection
    {
        $query = ActivitySubmission::query()->with('activity', 'enrollment.participant', 'enrollment.program', 'evidences');

        if ($user && $user->hasRole('parent')) {
            $participantIds = $user->guardianships()->pluck('participants.id');
            $query->whereHas('enrollment', fn ($q) => $q->whereIn('participant_id', $participantIds));
        }

        if (! empty($filters['status'])) {
            $query->where('status', SubmissionStatus::from($filters['status']));
        }

        if (! empty($filters['enrollment_id'])) {
            $query->where('enrollment_id', $filters['enrollment_id']);
        }

        if (! empty($filters['activity_id'])) {
            $query->where('activity_id', $filters['activity_id']);
        }

        return $query->get();
    }

    public function submit(User $user, Activity $activity, Enrollment $enrollment, array $data): ActivitySubmission
    {
        abort_if($enrollment->status !== EnrollmentStatus::Active, 422, 'This enrollment is no longer active.');

        abort_unless(
            $activity->module->program_id === $enrollment->program_id,
            422,
            'This activity does not belong to the enrollment program.',
        );

        $isGuardian = $enrollment->participant->guardians()
            ->where('users.id', $user->id)
            ->exists();

        abort_unless($isGuardian || $user->can('submit_evidence'), 403, 'You are not authorized to submit evidence.');

        return DB::transaction(function () use ($user, $activity, $enrollment, $data) {
            $submission = ActivitySubmission::updateOrCreate(
                ['enrollment_id' => $enrollment->id, 'activity_id' => $activity->id],
                [
                    'status' => SubmissionStatus::Submitted,
                    'submitted_at' => now(),
                    'reviewed_at' => null,
                ],
            );

            $this->createEvidence($user, $submission, $enrollment, $data);

            return $submission->load('evidences', 'activity');
        });
    }

    public function review(ActivitySubmission $submission, User $reviewer, array $data): ActivitySubmission
    {
        return DB::transaction(function () use ($submission, $reviewer, $data) {
            $status = $data['status'] === 'rejected'
                ? SubmissionStatus::Rejected
                : SubmissionStatus::Approved;

            $submission->update([
                'status' => $status,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            if (! empty($data['observation'])) {
                FieldNote::create([
                    'author_user_id' => $reviewer->id,
                    'participant_id' => $submission->enrollment->participant_id,
                    'activity_submission_id' => $submission->id,
                    'session_date' => now()->toDateString(),
                    'content' => $data['observation'],
                    'visibility' => FieldNoteVisibility::Private,
                ]);
            }

            SubmissionReviewed::dispatch($submission->enrollment);

            return $submission->load('evidences', 'activity', 'enrollment.participant', 'enrollment.program', 'reviewedBy');
        });
    }

    protected function createEvidence(User $user, ActivitySubmission $submission, Enrollment $enrollment, array $data): void
    {
        $type = EvidenceType::tryFrom($data['evidence_type'] ?? 'text') ?? EvidenceType::Text;

        $content = $data['content'] ?? null;

        if ($type === EvidenceType::Image && ! empty($data['evidence_file'])) {
            $content = app(EvidenceService::class)->storeFile($data['evidence_file'], $enrollment->tenant_id);
        }

        abort_unless($content, 422, 'Evidence content or file is required.');

        Evidence::create([
            'activity_submission_id' => $submission->id,
            'participant_id' => $enrollment->participant_id,
            'user_id' => $user->id,
            'type' => $type,
            'content' => $content,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }
}
