<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'module_id', 'name', 'description', 'type', 'order', 'config'])]
class Activity extends Model
{
    use HasUuids, BelongsToTenant;

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'config' => 'array',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ActivitySubmission::class);
    }
}
