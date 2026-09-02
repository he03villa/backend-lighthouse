<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Helpers\TenantTestHelpers;
use Tests\TestCase;

class ForumFlowTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_create_post_and_list(): void
    {
        $owner = $this->registerWithTenant(['email' => 'coach@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/forum/posts', [
                'title' => 'Consejos para sesiones',
                'content' => 'Comparto mis experiencias...',
                'category' => 'tips',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'Consejos para sesiones');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/forum/posts')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_add_comment_to_post(): void
    {
        $owner = $this->registerWithTenant(['email' => 'coach@example.com']);

        $postId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/forum/posts', [
                'title' => 'Pregunta',
                'content' => '¿Cómo manejar?',
            ])
            ->assertStatus(201)
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/forum/posts/'.$postId.'/comments', [
                'content' => 'Buena pregunta',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.content', 'Buena pregunta');
    }

    public function test_toggle_reaction_on_post(): void
    {
        $owner = $this->registerWithTenant(['email' => 'coach@example.com']);

        $postId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/forum/posts', [
                'title' => 'Post',
                'content' => 'Contenido',
            ])
            ->assertStatus(201)
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/forum/posts/'.$postId.'/reactions', ['type' => 'like'])
            ->assertStatus(200)
            ->assertJsonPath('data.added', true);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/forum/posts/'.$postId.'/reactions', ['type' => 'like'])
            ->assertStatus(200)
            ->assertJsonPath('data.added', false);
    }

    public function test_author_can_update_own_post(): void
    {
        $owner = $this->registerWithTenant(['email' => 'coach@example.com']);

        $postId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/forum/posts', [
                'title' => 'Original',
                'content' => 'Contenido',
            ])
            ->assertStatus(201)
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->patchJson('/api/v1/forum/posts/'.$postId, ['title' => 'Actualizado'])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Actualizado');
    }

    public function test_non_author_cannot_update_post(): void
    {
        $owner = $this->registerWithTenant(['email' => 'coach@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/tenants/'.$owner['tenantId'].'/members', [
                'email' => 'other@example.com',
                'role' => 'coach',
            ]);

        $postId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/forum/posts', [
                'title' => 'Del coach',
                'content' => 'Contenido',
            ])
            ->assertStatus(201)
            ->json('data.id');

        $other = User::where('email', 'other@example.com')->first();
        $otherToken = JWTAuth::fromUser($other);

        $this->authedApi($otherToken, $owner['tenantId'])
            ->patchJson('/api/v1/forum/posts/'.$postId, ['title' => 'Hack'])
            ->assertStatus(403);
    }

    public function test_admin_can_moderate_others_post(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/tenants/'.$owner['tenantId'].'/members', [
                'email' => 'coach@example.com',
                'role' => 'coach',
            ]);

        $coach = User::where('email', 'coach@example.com')->first();
        $coachToken = JWTAuth::fromUser($coach);

        $postId = $this->authedApi($coachToken, $owner['tenantId'])
            ->postJson('/api/v1/forum/posts', [
                'title' => 'Del coach',
                'content' => 'Contenido',
            ])
            ->assertStatus(201)
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->patchJson('/api/v1/forum/posts/'.$postId, ['pinned' => true])
            ->assertStatus(200)
            ->assertJsonPath('data.pinned', true);
    }

    public function test_forum_is_scoped_to_tenant(): void
    {
        $ownerA = $this->registerWithTenant(['email' => 'a@example.com']);
        $ownerB = $this->registerWithTenant(['email' => 'b@example.com']);

        $postId = $this->authedApi($ownerA['token'], $ownerA['tenantId'])
            ->postJson('/api/v1/forum/posts', [
                'title' => 'Solo A',
                'content' => 'Contenido',
            ])
            ->assertStatus(201)
            ->json('data.id');

        $this->authedApi($ownerB['token'], $ownerB['tenantId'])
            ->getJson('/api/v1/forum/posts/'.$postId)
            ->assertStatus(404);
    }
}
