<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['tenant_id', 'name', 'description'])]
class PlanningBoard extends Model
{
    use BelongsToTenant, HasUuids;

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(PlanningColumn::class, 'board_id')->orderBy('position');
    }

    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(PlanningTask::class, PlanningColumn::class, 'board_id', 'column_id');
    }
}
