<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_id_is_added_to_response(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertHeader('X-Request-Id');
    }

    public function test_custom_request_id_is_preserved(): void
    {
        $customId = 'custom-request-id-123';

        $response = $this->withHeaders(['X-Request-Id' => $customId])
            ->getJson('/api/health');

        $response->assertHeader('X-Request-Id', $customId);
    }
}
