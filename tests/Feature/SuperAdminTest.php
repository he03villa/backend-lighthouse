<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Helpers\TenantTestHelpers;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    public function test_super_admin_can_access_tenant_they_do_not_belong_to(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $result = $this->registerWithTenant(['email' => 'owner@example.com']);

        $superAdmin = User::updateOrCreate(
            ['email' => 'super@lighthouse.test'],
            ['name' => 'Super Admin', 'password' => bcrypt('password'), 'is_super_admin' => true]
        );

        $token = JWTAuth::fromUser($superAdmin);

        $this->authedApi($token, $result['tenantId'])
            ->getJson('/api/v1/tenants/'.$result['tenantId'])
            ->assertStatus(200);
    }

    public function test_regular_user_without_membership_is_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $result = $this->registerWithTenant(['email' => 'owner@example.com']);

        $stranger = User::updateOrCreate(
            ['email' => 'stranger@example.com'],
            ['name' => 'Stranger', 'password' => bcrypt('password')]
        );

        $token = JWTAuth::fromUser($stranger);

        $this->authedApi($token, $result['tenantId'])
            ->getJson('/api/v1/tenants/'.$result['tenantId'])
            ->assertStatus(403);
    }
}
