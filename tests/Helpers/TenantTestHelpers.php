<?php

namespace Tests\Helpers;

trait TenantTestHelpers
{
    protected function registerWithTenant(array $overrides = []): array
    {
        $payload = array_merge([
            'name' => 'Coach Uno',
            'email' => 'coach@example.com',
            'password' => 'password123',
            'tenant_name' => 'Club Deportivo',
        ], $overrides);

        $response = $this->postJson('/api/v1/auth/register', $payload);
        $response->assertStatus(201);

        return [
            'token' => $response->json('data.token'),
            'tenantId' => $response->json('data.tenant.id'),
            'user' => $response->json('data.user'),
        ];
    }

    protected function authedApi(string $token, ?string $tenantId = null): self
    {
        $this->withToken($token);

        if ($tenantId) {
            $this->withHeaders(['X-Tenant-Id' => $tenantId]);
        }

        return $this;
    }
}
