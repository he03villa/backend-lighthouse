<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Helpers\TenantTestHelpers;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_list_their_tenants(): void
    {
        $result = $this->registerWithTenant();

        $this->authedApi($result['token'])
            ->getJson('/api/v1/tenants')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_create_additional_tenant(): void
    {
        $result = $this->registerWithTenant();

        $this->authedApi($result['token'])
            ->postJson('/api/v1/tenants', ['name' => 'Segundo Club'])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Segundo Club');
    }

    public function test_tenant_endpoints_require_x_tenant_id(): void
    {
        $result = $this->registerWithTenant();

        $this->withToken($result['token'])
            ->getJson('/api/v1/tenants/'.$result['tenantId'])
            ->assertStatus(400);
    }

    public function test_owner_can_invite_member_with_role(): void
    {
        $result = $this->registerWithTenant();

        $this->authedApi($result['token'], $result['tenantId'])
            ->postJson('/api/v1/tenants/'.$result['tenantId'].'/members', [
                'email' => 'family@example.com',
                'role' => 'parent',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.email', 'family@example.com')
            ->assertJsonPath('data.roles.0', 'parent');
    }

    public function test_owner_can_invite_staff_member(): void
    {
        $result = $this->registerWithTenant();

        $this->authedApi($result['token'], $result['tenantId'])
            ->postJson('/api/v1/tenants/'.$result['tenantId'].'/members', [
                'email' => 'asistente@example.com',
                'role' => 'staff',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.roles.0', 'staff');
    }

    public function test_member_list_shows_roles(): void
    {
        $result = $this->registerWithTenant();

        $this->authedApi($result['token'], $result['tenantId'])
            ->postJson('/api/v1/tenants/'.$result['tenantId'].'/members', [
                'email' => 'entrenador@example.com',
                'role' => 'coach',
            ])
            ->assertStatus(201);

        $this->authedApi($result['token'], $result['tenantId'])
            ->getJson('/api/v1/tenants/'.$result['tenantId'].'/members')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_owner_can_update_member_role(): void
    {
        $result = $this->registerWithTenant();

        $invited = $this->authedApi($result['token'], $result['tenantId'])
            ->postJson('/api/v1/tenants/'.$result['tenantId'].'/members', [
                'email' => 'family@example.com',
                'role' => 'parent',
            ])
            ->json('data');

        $this->authedApi($result['token'], $result['tenantId'])
            ->patchJson('/api/v1/tenants/'.$result['tenantId'].'/members/'.$invited['id'], [
                'role' => 'admin',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.roles.0', 'admin');
    }

    public function test_owner_can_remove_member(): void
    {
        $result = $this->registerWithTenant();

        $invited = $this->authedApi($result['token'], $result['tenantId'])
            ->postJson('/api/v1/tenants/'.$result['tenantId'].'/members', [
                'email' => 'family@example.com',
                'role' => 'parent',
            ])
            ->json('data');

        $this->authedApi($result['token'], $result['tenantId'])
            ->deleteJson('/api/v1/tenants/'.$result['tenantId'].'/members/'.$invited['id'])
            ->assertStatus(200);

        $this->authedApi($result['token'], $result['tenantId'])
            ->getJson('/api/v1/tenants/'.$result['tenantId'].'/members')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_member_without_manage_tenant_cannot_invite(): void
    {
        $result = $this->registerWithTenant();

        $invited = $this->authedApi($result['token'], $result['tenantId'])
            ->postJson('/api/v1/tenants/'.$result['tenantId'].'/members', [
                'email' => 'family@example.com',
                'role' => 'parent',
            ])
            ->json('data');

        $parent = User::where('email', 'family@example.com')->first();
        $parentToken = JWTAuth::fromUser($parent);

        $this->authedApi($parentToken, $result['tenantId'])
            ->postJson('/api/v1/tenants/'.$result['tenantId'].'/members', [
                'email' => 'otro@example.com',
                'role' => 'coach',
            ])
            ->assertStatus(403);
    }

    public function test_non_member_cannot_access_tenant(): void
    {
        $resultA = $this->registerWithTenant(['email' => 'a@example.com']);
        $resultB = $this->registerWithTenant(['email' => 'b@example.com']);

        $this->authedApi($resultB['token'], $resultA['tenantId'])
            ->getJson('/api/v1/tenants/'.$resultA['tenantId'])
            ->assertStatus(403);
    }
}
