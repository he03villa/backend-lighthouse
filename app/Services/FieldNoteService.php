<?php

namespace App\Services;

use App\Enums\FieldNoteVisibility;
use App\Models\FieldNote;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class FieldNoteService
{
    public function list(?Participant $participant = null): Collection
    {
        $query = FieldNote::query()->with('author', 'participant', 'submission.activity');

        if ($participant) {
            $query->where('participant_id', $participant->id);
        }

        return $query->get();
    }

    public function create(User $author, array $data): FieldNote
    {
        return FieldNote::create([
            'author_user_id' => $author->id,
            'participant_id' => $data['participant_id'],
            'activity_submission_id' => $data['activity_submission_id'] ?? null,
            'session_date' => $data['session_date'] ?? now()->toDateString(),
            'content' => $data['content'],
            'visibility' => FieldNoteVisibility::from($data['visibility'] ?? 'private'),
        ])->load('author');
    }
}
