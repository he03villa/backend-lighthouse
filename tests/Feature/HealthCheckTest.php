<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure([
                'status',
                'timestamp',
                'checks' => [
                    'database',
                    'cache',
                    'queue',
                ],
            ]);
    }

    public function test_health_endpoint_includes_latency(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonPath('checks.cache.latency_ms', fn ($latency) => $latency > 0);
    }
}
