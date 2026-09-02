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
        'manage_planning',
        'view_all_progress',
        'view_own_progress',
        'submit_evidence',
        'review_evidence',
        'write_journal',
        'read_field_notes',
        'write_field_notes',
        'use_ai_assistant',
        'create_conversations',
        'send_messages',
        'create_forum_posts',
        'moderate_forum_posts',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
