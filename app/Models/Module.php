<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'program_id', 'name', 'description', 'order'])]
class Module extends Model
{
    use BelongsToTenant, HasUuids;

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->orderBy('order');
    }
}
