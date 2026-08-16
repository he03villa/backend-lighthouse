<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['column_id', 'title', 'description', 'start_date', 'due_date', 'position'])]
class PlanningTask extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(PlanningColumn::class, 'column_id');
    }
}
