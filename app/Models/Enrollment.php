<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['tenant_id', 'participant_id', 'program_id', 'status', 'enrolled_at', 'completed_at'])]
class Enrollment extends Model
{
    use HasUuids, BelongsToTenant;

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'enrolled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ActivitySubmission::class);
    }

    public function progressRecord(): HasOne
    {
        return $this->hasOne(ProgressRecord::class);
    }
}
