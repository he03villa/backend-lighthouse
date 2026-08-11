<?php

namespace App\Models;

use App\Enums\TenantType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'type', 'config'])]
class Tenant extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'type' => TenantType::class,
            'config' => 'array',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->using(TenantUser::class)
            ->withPivot('is_active', 'joined_at')
            ->withTimestamps();
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }
}
