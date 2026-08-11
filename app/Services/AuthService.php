<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(protected TenantService $tenants)
    {
    }

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
            'user' => $user,
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
            'user' => $user->load('tenants'),
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
        return Auth::user()->load('tenants');
    }
}
