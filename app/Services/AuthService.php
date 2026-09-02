<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AuthService
{
    public function __construct(
        protected TenantService $tenants,
        protected PermissionRegistrar $permissions,
    ) {}

    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $tenant = null;

        if (! empty($data['tenant_name'])) {
            $tenant = $this->tenants->create($user, [
                'name' => $data['tenant_name'],
                'type' => $data['tenant_type'] ?? 'individual',
            ]);
        }

        $token = JWTAuth::fromUser($user);

        return [
            'user' => $this->userWithAccess($user),
            'token' => $token,
            'tenant' => $tenant,
        ];
    }

    public function login(array $credentials): ?array
    {
        if (! $token = JWTAuth::attempt($credentials)) {
            return null;
        }

        $user = JWTAuth::user();
        $user->forceFill(['last_login_at' => now()])->save();

        return [
            'user' => $this->userWithAccess($user),
            'token' => $token,
        ];
    }

    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    public function refresh(): string
    {
        return JWTAuth::refresh(JWTAuth::getToken());
    }

    public function me(): User
    {
        return $this->userWithAccess(Auth::user());
    }

    public function createInvitation(string $email, string $name): array
    {
        $token = Str::random(64);
        $expiresAt = now()->addDays(7);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(Str::password(20)),
                'invitation_token' => $token,
                'invitation_expires_at' => $expiresAt,
            ]
        );

        if (! $user->invitation_token) {
            $user->update([
                'invitation_token' => $token,
                'invitation_expires_at' => $expiresAt,
            ]);
        }

        return [
            'user' => $user,
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    public function acceptInvitation(string $token, string $name, string $password): ?array
    {
        $user = User::where('invitation_token', $token)
            ->where('invitation_expires_at', '>', now())
            ->first();

        if (! $user) {
            return null;
        }

        $user->update([
            'name' => $name,
            'password' => Hash::make($password),
            'invitation_token' => null,
            'invitation_expires_at' => null,
        ]);

        $jwtToken = JWTAuth::fromUser($user);

        return [
            'user' => $this->userWithAccess($user),
            'token' => $jwtToken,
        ];
    }

    public function userWithAccess(User $user): User
    {
        $user->load('tenants');

        return $user->setRelation('access', $this->accessData($user));
    }

    protected function accessData(User $user): array
    {
        $roles = [];
        $permissions = [];

        $tenants = $user->tenants->map(function (Tenant $tenant) use ($user, &$roles, &$permissions) {
            $this->permissions->setPermissionsTeamId($tenant->id);

            $tenantRoles = $user->roles()->pluck('name')->values()->all();
            $tenantPermissions = $user->roles()->with('permissions')->get()
                ->flatMap(fn ($role) => $role->permissions->pluck('name'))
                ->unique()->sort()->values()->all();

            $roles = array_merge($roles, $tenantRoles);
            $permissions = array_merge($permissions, $tenantPermissions);

            return [
                'tenant' => $tenant,
                'roles' => $tenantRoles,
                'permissions' => $tenantPermissions,
            ];
        })->values();

        if ($user->is_super_admin) {
            $roles = ['super-admin'];
            $permissions = Permission::where('guard_name', 'api')->pluck('name')->sort()->values()->all();
        }

        return [
            'tenants' => $tenants,
            'roles' => array_values(array_unique($roles)),
            'permissions' => array_values(array_unique($permissions)),
        ];
    }
}
