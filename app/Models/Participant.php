<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_id', 'first_name', 'last_name', 'birth_date', 'avatar', 'metadata'])]
class Participant extends Model
{
    use BelongsToTenant, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'participant_guardians')
            ->withPivot('relationship', 'is_primary', 'permissions');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_members');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function fieldNotes(): HasMany
    {
        return $this->hasMany(FieldNote::class);
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(ProgressRecord::class);
    }

    public function submissions(): HasManyThrough
    {
        return $this->hasManyThrough(ActivitySubmission::class, Enrollment::class);
    }
}
