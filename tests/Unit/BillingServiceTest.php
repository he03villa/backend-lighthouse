<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\BillingService;
use App\Tenancy\TenantContext;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Checkout;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionBuilder;
use Mockery;
use Stripe\Checkout\Session;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    public function test_create_subscription_returns_checkout_url(): void
    {
        $pro = Plan::where('slug', 'pro')->first();
        $pro->update(['stripe_price_id_monthly' => 'price_test_pro']);

        $tenant = Mockery::mock(Tenant::class)->makePartial();

        $session = new Session('cs_test_1');
        $session->url = 'https://checkout.stripe.com/c/pay/cs_test_1';

        $builder = Mockery::mock(SubscriptionBuilder::class);
        $builder->shouldReceive('checkout')->once()->andReturn(new Checkout($tenant, $session));

        $tenant->shouldReceive('subscribed')->with('default')->andReturn(false);
        $tenant->shouldReceive('newSubscription')->with('default', 'price_test_pro')->andReturn($builder);

        app(TenantContext::class)->set($tenant);

        $result = app(BillingService::class)->createSubscription(
            'pro',
            'https://app.example.com/success',
            'https://app.example.com/cancel',
        );

        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_1', $result['url']);
        $this->assertSame('cs_test_1', $result['session_id']);
    }

    public function test_swap_updates_subscription_to_new_price(): void
    {
        $pro = Plan::where('slug', 'pro')->first();
        $pro->update(['stripe_price_id_monthly' => 'price_test_pro']);

        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $subscription = Mockery::mock(Subscription::class)->makePartial();
        $subscription->stripe_price = 'price_test_pro';
        $subscription->stripe_status = 'active';
        $subscription->shouldReceive('swap')->with('price_test_pro')->once();

        $tenant->shouldReceive('subscription')->with('default')->andReturn($subscription);

        app(TenantContext::class)->set($tenant);

        $result = app(BillingService::class)->swap('pro');

        $this->assertSame('price_test_pro', $result['price']);
        $this->assertSame('active', $result['status']);
    }

    public function test_cancel_cancels_active_subscription(): void
    {
        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $subscription = Mockery::mock(Subscription::class)->makePartial();
        $subscription->stripe_status = 'active';
        $subscription->ends_at = null;
        $subscription->shouldReceive('active')->andReturn(true);
        $subscription->shouldReceive('cancel')->once();

        $tenant->shouldReceive('subscription')->with('default')->andReturn($subscription);

        app(TenantContext::class)->set($tenant);

        $result = app(BillingService::class)->cancel();

        $this->assertSame('active', $result['status']);
    }

    public function test_portal_returns_billing_portal_url(): void
    {
        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->shouldReceive('billingPortalUrl')->andReturn('https://billing.stripe.com/session/test');

        app(TenantContext::class)->set($tenant);

        $this->assertSame('https://billing.stripe.com/session/test', app(BillingService::class)->portalUrl());
    }
}
