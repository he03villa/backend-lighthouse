<?php

namespace App\Models;

use App\Enums\EvidenceType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'activity_submission_id', 'participant_id', 'user_id', 'type', 'content', 'metadata'])]
class Evidence extends Model
{
    use HasUuids, BelongsToTenant;

    protected $table = 'evidences';

    protected function casts(): array
    {
        return [
            'type' => EvidenceType::class,
            'metadata' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ActivitySubmission::class, 'activity_submission_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
