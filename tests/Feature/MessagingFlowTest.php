<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Helpers\TenantTestHelpers;
use Tests\TestCase;

class MessagingFlowTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_create_conversation_and_send_message(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/tenants/'.$owner['tenantId'].'/members', [
                'email' => 'coach@example.com',
                'role' => 'coach',
            ])
            ->assertStatus(201);

        $coach = User::where('email', 'coach@example.com')->first();
        $coachToken = JWTAuth::fromUser($coach);

        $conversationId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/conversations', [
                'user_ids' => [$coach->id],
                'title' => 'Sobre el progreso de Juan',
            ])
            ->assertStatus(201)
            ->json('data.id');

        $this->authedApi($coachToken, $owner['tenantId'])
            ->postJson('/api/v1/conversations/'.$conversationId.'/messages', [
                'content' => 'Hola, ¿cómo va Juan?',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.content', 'Hola, ¿cómo va Juan?');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/conversations/'.$conversationId.'/messages')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.messages');
    }

    public function test_non_participant_cannot_send_message(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/tenants/'.$owner['tenantId'].'/members', [
                'email' => 'coach@example.com',
                'role' => 'coach',
            ]);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/tenants/'.$owner['tenantId'].'/members', [
                'email' => 'outsider@example.com',
                'role' => 'coach',
            ]);

        $coach = User::where('email', 'coach@example.com')->first();

        $conversationId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/conversations', [
                'user_ids' => [$coach->id],
            ])
            ->assertStatus(201)
            ->json('data.id');

        $outsider = User::where('email', 'outsider@example.com')->first();
        $outsiderToken = JWTAuth::fromUser($outsider);

        $this->authedApi($outsiderToken, $owner['tenantId'])
            ->postJson('/api/v1/conversations/'.$conversationId.'/messages', [
                'content' => 'No debería poder',
            ])
            ->assertStatus(403);
    }

    public function test_conversation_is_scoped_to_tenant(): void
    {
        $ownerA = $this->registerWithTenant(['email' => 'a@example.com']);
        $ownerB = $this->registerWithTenant(['email' => 'b@example.com']);

        $conversationId = $this->authedApi($ownerA['token'], $ownerA['tenantId'])
            ->postJson('/api/v1/conversations', [
                'user_ids' => [$ownerA['user']['id']],
            ])
            ->assertStatus(201)
            ->json('data.id');

        $this->authedApi($ownerB['token'], $ownerB['tenantId'])
            ->getJson('/api/v1/conversations/'.$conversationId)
            ->assertStatus(404);
    }

    public function test_mark_as_read(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $conversationId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/conversations', [
                'user_ids' => [$owner['user']['id']],
            ])
            ->assertStatus(201)
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->patchJson('/api/v1/conversations/'.$conversationId.'/read')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Conversation marked as read');
    }

    public function test_list_conversations(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/conversations', ['user_ids' => [$owner['user']['id']]])
            ->assertStatus(201);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/conversations')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
