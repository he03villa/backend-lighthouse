<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_register_with_tenant_creates_tenant_and_owner(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'password123',
            'tenant_name' => 'Mi Club',
            'tenant_type' => 'organization',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.tenant.name', 'Mi Club')
            ->assertJsonPath('data.tenant.type', 'organization');

        $user = User::where('email', 'owner@example.com')->first();

        $this->assertTrue($user->tenants()->exists());

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($response->json('data.tenant.id'));
        $this->assertTrue($user->hasRole('owner'));
    }

    public function test_register_rejects_duplicate_email(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'A',
            'email' => 'dup@example.com',
            'password' => 'password123',
        ])->assertStatus(201);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'B',
            'email' => 'dup@example.com',
            'password' => 'password123',
        ])->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_login_returns_token_and_me_returns_user(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Login User',
            'email' => 'login@example.com',
            'password' => 'password123',
            'tenant_name' => 'Club',
        ])->assertStatus(201);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $login->assertStatus(200)
            ->assertJsonPath('data.user.email', 'login@example.com');

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('data.email', 'login@example.com');
    }

    public function test_login_with_invalid_credentials_returns_401(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong',
        ])->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_logout_invalidates_token(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Logout',
            'email' => 'logout@example.com',
            'password' => 'password123',
        ])->assertStatus(201);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'logout@example.com',
            'password' => 'password123',
        ])->assertStatus(200);

        $token = $login->json('data.token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertStatus(200);

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_refresh_returns_new_token(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $token = JWTAuth::fromUser($user);

        $this->withToken($token)
            ->postJson('/api/v1/auth/refresh')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);
    }
}
