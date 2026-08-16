<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Tenancy\TenantContext;

class TenantLimits
{
    public function __construct(protected TenantContext $tenantContext) {}

    public function plan(?Tenant $tenant = null): Plan
    {
        $tenant ??= $this->tenantContext->current();

        if ($tenant && $subscription = $tenant->subscription('default')) {
            if ($subscription->active()) {
                $price = $subscription->stripe_price ?? $subscription->items()->first()?->stripe_price;

                if ($price) {
                    $plan = Plan::where('stripe_price_id_monthly', $price)
                        ->orWhere('stripe_price_id_yearly', $price)
                        ->first();

                    if ($plan) {
                        return $plan;
                    }
                }
            }
        }

        return Plan::where('slug', 'free')->first() ?? new Plan(['limits' => []]);
    }

    public function limit(string $key, ?Tenant $tenant = null): ?int
    {
        $value = $this->plan($tenant)->limits[$key] ?? null;

        return $value === null ? null : (int) $value;
    }

    public function assertWithinLimit(string $key, int $current, ?Tenant $tenant = null): void
    {
        $limit = $this->limit($key, $tenant);

        if ($limit !== null && $current >= $limit) {
            abort(422, "Se alcanzó el límite del plan: {$key}.");
        }
    }
}
