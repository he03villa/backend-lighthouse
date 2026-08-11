<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'manage_tenant',
        'manage_programs',
        'manage_participants',
        'view_all_progress',
        'view_own_progress',
        'submit_evidence',
        'review_evidence',
        'write_journal',
        'write_field_notes',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
