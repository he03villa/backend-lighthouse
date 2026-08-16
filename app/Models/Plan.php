<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'description', 'price_monthly', 'price_yearly', 'currency', 'features', 'limits', 'stripe_price_id_monthly', 'stripe_price_id_yearly', 'is_active'])]
class Plan extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'price_monthly' => 'integer',
            'price_yearly' => 'integer',
            'features' => 'array',
            'limits' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function limit(string $key, int $default = 0): int
    {
        return (int) ($this->limits[$key] ?? $default);
    }
}
