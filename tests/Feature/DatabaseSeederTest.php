<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_all_permissions_including_write_journal(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $names = Permission::where('guard_name', 'api')->pluck('name')->all();

        $this->assertEqualsCanonicalizing(RolePermissionSeeder::PERMISSIONS, $names);
        $this->assertContains('write_journal', $names);
    }

    public function test_seeder_marks_test_user_as_super_admin(): void
    {
        $this->seed();

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->is_super_admin);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, User::where('email', 'test@example.com')->count());
    }
}
