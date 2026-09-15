<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Helpers\TenantTestHelpers;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_must_verify_email_before_accessing_tenant_routes(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $token = JWTAuth::fromUser($user);

        $this->authedApi($token)
            ->getJson('/api/v1/tenants')
            ->assertStatus(403);
    }

    public function test_verified_user_can_access_tenant_routes(): void
    {
        $result = $this->registerWithTenant(['email' => 'verified@example.com']);

        $user = User::where('email', 'verified@example.com')->first();
        $user->markEmailAsVerified();

        $this->authedApi($result['token'], $result['tenantId'])
            ->getJson('/api/v1/tenants/'.$result['tenantId'])
            ->assertStatus(200);
    }

    public function test_verify_email_endpoint(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $id = $user->getKey();
        $hash = sha1($user->getEmailForVerification());

        $this->getJson("/api/v1/auth/verify-email/{$id}/{$hash}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_verify_email_with_invalid_hash_returns_404(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $id = $user->getKey();
        $hash = 'invalid-hash';

        $this->getJson("/api/v1/auth/verify-email/{$id}/{$hash}")
            ->assertStatus(404);
    }

    public function test_send_verification_notification(): void
    {
        Notification::fake();

        $result = $this->registerWithTenant(['email' => 'notify@example.com']);

        $user = User::where('email', 'notify@example.com')->first();
        $user->email_verified_at = null;
        $user->save();

        $this->authedApi($result['token'])
            ->postJson('/api/v1/auth/email/verification-notification')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_already_verified_user_gets_appropriate_message(): void
    {
        $result = $this->registerWithTenant(['email' => 'already@example.com']);

        $user = User::where('email', 'already@example.com')->first();
        $user->markEmailAsVerified();

        $this->authedApi($result['token'])
            ->postJson('/api/v1/auth/email/verification-notification')
            ->assertStatus(200)
            ->assertJsonPath('data', null);
    }
}
