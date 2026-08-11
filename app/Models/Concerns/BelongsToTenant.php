<?php

namespace App\Models\Concerns;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $builder->where($builder->qualifyColumn('tenant_id'), app(TenantContext::class)->id());
        });

        static::creating(function (Model $model) {
            $model->tenant_id ??= app(TenantContext::class)->id();
        });
    }
}
