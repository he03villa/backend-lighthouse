<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Helpers\TenantTestHelpers;
use Tests\TestCase;

class BillingFlowTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(PlanSeeder::class);
    }

    public function test_owner_can_list_plans(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/billing/plans')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.slug', 'free')
            ->assertJsonPath('data.1.slug', 'pro')
            ->assertJsonPath('data.2.slug', 'elite');
    }

    public function test_free_plan_blocks_creating_more_than_ten_participants(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        for ($i = 1; $i <= 10; $i++) {
            $this->authedApi($owner['token'], $owner['tenantId'])
                ->postJson('/api/v1/participants', [
                    'first_name' => "Nino $i",
                    'last_name' => 'Participante',
                ])
                ->assertStatus(201);
        }

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/participants', [
                'first_name' => 'Nino 11',
                'last_name' => 'Participante',
            ])
            ->assertStatus(422);
    }

    public function test_free_plan_blocks_inviting_coaches_but_allows_parents(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/tenants/'.$owner['tenantId'].'/members', [
                'email' => 'coach@example.com',
                'role' => 'coach',
            ])
            ->assertStatus(422);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/tenants/'.$owner['tenantId'].'/members', [
                'email' => 'madre@example.com',
                'role' => 'parent',
            ])
            ->assertStatus(201);
    }

    public function test_parent_cannot_access_billing(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/tenants/'.$owner['tenantId'].'/members', [
                'email' => 'madre@example.com',
                'role' => 'parent',
            ])
            ->assertStatus(201);

        $mother = User::where('email', 'madre@example.com')->first();
        $motherToken = JWTAuth::fromUser($mother);

        $this->authedApi($motherToken, $owner['tenantId'])
            ->postJson('/api/v1/billing/subscriptions', [
                'plan_slug' => 'pro',
                'success_url' => 'https://app.example.com/success',
                'cancel_url' => 'https://app.example.com/cancel',
            ])
            ->assertStatus(403);
    }

    public function test_current_shows_resolved_plan_and_usage(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);
        $tenant = Tenant::find($owner['tenantId']);

        $pro = Plan::where('slug', 'pro')->first();
        $pro->update(['stripe_price_id_monthly' => 'price_test_pro']);

        $tenant->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_123',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test_pro',
            'quantity' => 1,
        ]);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/billing/current')
            ->assertStatus(200)
            ->assertJsonPath('data.plan.slug', 'pro')
            ->assertJsonPath('data.subscription.status', 'active')
            ->assertJsonPath('data.usage.participants', 0);
    }

    public function test_webhook_mirrors_paid_invoice(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);
        $tenant = Tenant::find($owner['tenantId']);
        $tenant->forceFill(['stripe_id' => 'cus_test_123'])->save();

        $payload = [
            'id' => 'evt_test_1',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_test_1',
                    'customer' => 'cus_test_123',
                    'subscription' => null,
                    'amount_paid' => 4900,
                    'currency' => 'usd',
                    'status' => 'paid',
                    'hosted_invoice_url' => 'https://pay.stripe.com/invoice/in_test_1',
                    'invoice_pdf' => 'https://pay.stripe.com/invoice/in_test_1/pdf',
                    'due_date' => null,
                    'status_paid_at' => now()->timestamp,
                ],
            ],
        ];

        $this->postJson('/api/v1/stripe/webhook', $payload)->assertStatus(200);

        $invoice = Invoice::withoutGlobalScope('tenant')->first();

        $this->assertNotNull($invoice);
        $this->assertSame('in_test_1', $invoice->stripe_invoice_id);
        $this->assertSame(4900, $invoice->amount);
        $this->assertSame('paid', $invoice->status);
        $this->assertSame($tenant->id, $invoice->tenant_id);
    }
}
