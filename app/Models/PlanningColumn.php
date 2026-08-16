<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['board_id', 'name', 'position'])]
class PlanningColumn extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(PlanningBoard::class, 'board_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(PlanningTask::class, 'column_id')->orderBy('position');
    }
}
