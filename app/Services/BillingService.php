<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Cashier\Checkout;

class BillingService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected TenantService $tenantService,
        protected TenantLimits $tenantLimits,
    ) {}

    public function tenant(): Tenant
    {
        return $this->tenantContext->current() ?? abort(400, 'Tenant context is not initialized.');
    }

    public function plans(): Collection
    {
        return Plan::query()->where('is_active', true)->get();
    }

    public function current(): array
    {
        $tenant = $this->tenant();
        $subscription = $tenant->subscription('default');
        $plan = $this->tenantLimits->plan($tenant);

        return [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'plan' => $plan,
            'subscription' => $subscription ? [
                'status' => $subscription->stripe_status,
                'on_grace_period' => $subscription->onGracePeriod(),
                'ends_at' => $subscription->ends_at?->toISOString(),
                'price' => $subscription->stripe_price,
            ] : null,
            'usage' => [
                'participants' => $tenant->participants()->count(),
                'coaches' => $this->tenantService->countCoaches($tenant),
            ],
        ];
    }

    public function createSubscription(string $planSlug, string $successUrl, string $cancelUrl): array
    {
        $plan = $this->resolveActivePlan($planSlug);
        $tenant = $this->tenant();

        abort_if($tenant->subscribed('default'), 422, 'This tenant already has an active subscription.');

        $checkout = $tenant->newSubscription('default', $this->monthlyPrice($plan))->checkout([
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => ['tenant_id' => $tenant->id],
        ]);

        return $this->checkoutPayload($checkout);
    }

    public function swap(string $planSlug): array
    {
        $plan = $this->resolveActivePlan($planSlug);
        $tenant = $this->tenant();
        $subscription = $tenant->subscription('default');

        abort_unless($subscription, 422, 'This tenant does not have a subscription to swap.');

        $subscription->swap($this->monthlyPrice($plan));

        return [
            'status' => $subscription->stripe_status,
            'price' => $subscription->stripe_price,
        ];
    }

    public function cancel(): array
    {
        $tenant = $this->tenant();
        $subscription = $tenant->subscription('default');

        abort_unless($subscription?->active(), 422, 'This tenant does not have an active subscription.');

        $subscription->cancel();

        return [
            'status' => $subscription->stripe_status,
            'ends_at' => $subscription->ends_at?->toISOString(),
        ];
    }

    public function portalUrl(): string
    {
        return $this->tenant()->billingPortalUrl();
    }

    public function invoices(): Collection
    {
        return $this->tenant()->invoices()->latest('due_date')->latest()->get();
    }

    protected function resolveActivePlan(string $slug): Plan
    {
        return Plan::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    protected function monthlyPrice(Plan $plan): string
    {
        abort_unless($plan->stripe_price_id_monthly, 422, "Plan {$plan->slug} is not ready for checkout.");

        return $plan->stripe_price_id_monthly;
    }

    protected function checkoutPayload(Checkout $checkout): array
    {
        return [
            'url' => $checkout->url,
            'session_id' => $checkout->id,
        ];
    }
}
