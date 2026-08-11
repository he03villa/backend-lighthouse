<?php

namespace App\Models;

use App\Enums\FieldNoteVisibility;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'author_user_id', 'participant_id', 'activity_submission_id', 'session_date', 'content', 'visibility'])]
class FieldNote extends Model
{
    use HasUuids, BelongsToTenant;

    protected function casts(): array
    {
        return [
            'visibility' => FieldNoteVisibility::class,
            'session_date' => 'date',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ActivitySubmission::class, 'activity_submission_id');
    }
}
