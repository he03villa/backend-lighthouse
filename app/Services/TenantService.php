<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantService
{
    private const ROLE_PERMISSIONS = [
        'owner' => [
            'manage_tenant',
            'manage_programs',
            'manage_participants',
            'manage_planning',
            'view_all_progress',
            'view_own_progress',
            'submit_evidence',
            'review_evidence',
            'write_journal',
            'write_field_notes',
        ],
        'admin' => [
            'manage_tenant',
            'manage_programs',
            'manage_participants',
            'manage_planning',
            'view_all_progress',
            'review_evidence',
            'write_journal',
            'write_field_notes',
        ],
        'coach' => [
            'manage_programs',
            'manage_participants',
            'manage_planning',
            'view_all_progress',
            'review_evidence',
            'write_journal',
            'write_field_notes',
        ],
        'parent' => [
            'view_own_progress',
            'submit_evidence',
            'write_journal',
        ],
        'participant' => [
            'view_own_progress',
            'submit_evidence',
            'write_journal',
        ],
        'staff' => [],
    ];

    public function __construct(protected PermissionRegistrar $permissions) {}

    public function create(User $owner, array $data): Tenant
    {
        $tenant = Tenant::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
            'type' => $data['type'] ?? 'individual',
            'config' => $data['config'] ?? null,
        ]);

        $this->createRolesForTeam($tenant);

        $tenant->users()->attach($owner, [
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->permissions->setPermissionsTeamId($tenant->id);
        $owner->assignRole('owner');
        $this->permissions->forgetCachedPermissions();

        return $tenant;
    }

    public function listFor(User $user)
    {
        return $user->tenants()->get();
    }

    public function members(Tenant $tenant)
    {
        return $tenant->users()->get();
    }

    public function invite(User $actor, Tenant $tenant, string $email, string $role): User
    {
        $this->assertCanManage($actor, $tenant);

        if (in_array($role, ['coach', 'admin', 'staff'], true)) {
            $this->assertCoachLimit($tenant);
        }

        $user = User::firstOrCreate(['email' => $email], [
            'name' => Str::before($email, '@'),
            'password' => Str::password(20),
        ]);

        $this->addMemberWithRole($user, $tenant, $role);

        return $user;
    }

    public function updateRole(User $actor, Tenant $tenant, User $user, string $role): User
    {
        $this->assertCanManage($actor, $tenant);
        $this->assertMember($tenant, $user);

        $this->permissions->setPermissionsTeamId($tenant->id);
        $user->syncRoles([$role]);
        $this->permissions->forgetCachedPermissions();

        return $user;
    }

    public function addMemberWithRole(User $user, Tenant $tenant, string $role): void
    {
        if (! $tenant->users()->where('users.id', $user->id)->exists()) {
            $tenant->users()->attach($user, [
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

        $this->permissions->setPermissionsTeamId($tenant->id);
        $user->assignRole($role);
        $this->permissions->forgetCachedPermissions();
    }

    public function remove(User $actor, Tenant $tenant, User $user): void
    {
        $this->assertCanManage($actor, $tenant);

        if ($user->id === $actor->id) {
            abort(422, 'You cannot remove yourself from the tenant.');
        }

        $tenant->users()->detach($user);

        $this->permissions->setPermissionsTeamId($tenant->id);
        $user->syncRoles([]);
        $this->permissions->forgetCachedPermissions();
    }

    public function assertCanManage(User $actor, Tenant $tenant): void
    {
        $this->assertMember($tenant, $actor);

        $this->permissions->setPermissionsTeamId($tenant->id);

        abort_unless($actor->can('manage_tenant'), 403, 'You do not have permission to manage this tenant.');
    }

    protected function assertCoachLimit(Tenant $tenant): void
    {
        app(TenantLimits::class)->assertWithinLimit('max_coaches', $this->countCoaches($tenant), $tenant);
    }

    public function countCoaches(Tenant $tenant): int
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'coach')
            ->where('roles.guard_name', 'api')
            ->where('model_has_roles.tenant_id', $tenant->id)
            ->count();
    }

    public function assertMember(Tenant $tenant, User $user): void
    {
        abort_unless(
            $user->is_super_admin
            || $tenant->users()->where('users.id', $user->id)->exists(),
            403,
            'User is not a member of this tenant.'
        );
    }

    protected function createRolesForTeam(Tenant $tenant): void
    {
        $this->permissions->setPermissionsTeamId($tenant->id);

        foreach (self::ROLE_PERMISSIONS as $name => $permissions) {
            $role = Role::findOrCreate($name, 'api');
            $role->syncPermissions($permissions);
        }

        $this->permissions->forgetCachedPermissions();
    }
}
