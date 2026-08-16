<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Helpers\TenantTestHelpers;
use Tests\TestCase;

class PlanningFlowTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_creates_board_with_default_columns(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/planning/boards', ['name' => 'Plan semanal'])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Plan semanal')
            ->assertJsonCount(3, 'data.columns');

        $this->assertDatabaseCount('planning_boards', 1);
        $this->assertDatabaseCount('planning_columns', 3);
    }

    public function test_owner_lists_boards_with_counts(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/planning/boards', ['name' => 'Plan A'])
            ->assertStatus(201);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/planning/boards')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Plan A')
            ->assertJsonPath('data.0.columns_count', 3)
            ->assertJsonPath('data.0.tasks_count', 0);
    }

    public function test_owner_adds_column_and_task_to_board(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $boardId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/planning/boards', ['name' => 'Plan'])
            ->json('data.id');

        $columnId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/planning/boards/'.$boardId.'/columns', ['name' => 'Revisión'])
            ->assertStatus(201)
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/planning/columns/'.$columnId.'/tasks', [
                'title' => 'Preparar sesión',
                'due_date' => '2026-09-01',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'Preparar sesión')
            ->assertJsonPath('data.due_date', '2026-09-01');

        $this->assertDatabaseCount('planning_tasks', 1);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/planning/boards/'.$boardId)
            ->assertStatus(200)
            ->assertJsonPath('data.columns.3.tasks.0.title', 'Preparar sesión');
    }

    public function test_owner_moves_task_between_columns(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $board = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/planning/boards', ['name' => 'Plan'])
            ->json('data');

        $columnA = $board['columns'][0]['id'];
        $columnB = $board['columns'][1]['id'];

        $taskId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/planning/columns/'.$columnA.'/tasks', ['title' => 'Tarea A'])
            ->assertStatus(201)
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->patchJson('/api/v1/planning/tasks/'.$taskId.'/move', ['column_id' => $columnB])
            ->assertStatus(200)
            ->assertJsonPath('data.column_id', $columnB);

        $this->assertDatabaseHas('planning_tasks', [
            'id' => $taskId,
            'column_id' => $columnB,
            'position' => 0,
        ]);
    }

    public function test_board_is_scoped_to_tenant(): void
    {
        $ownerA = $this->registerWithTenant(['email' => 'a@example.com']);
        $ownerB = $this->registerWithTenant(['email' => 'b@example.com']);

        $boardId = $this->authedApi($ownerA['token'], $ownerA['tenantId'])
            ->postJson('/api/v1/planning/boards', ['name' => 'Solo de A'])
            ->assertStatus(201)
            ->json('data.id');

        $this->authedApi($ownerB['token'], $ownerB['tenantId'])
            ->getJson('/api/v1/planning/boards/'.$boardId)
            ->assertStatus(404);

        $this->authedApi($ownerB['token'], $ownerB['tenantId'])
            ->deleteJson('/api/v1/planning/boards/'.$boardId)
            ->assertStatus(404);
    }

    public function test_parent_cannot_manage_planning(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/tenants/'.$owner['tenantId'].'/members', [
                'email' => 'madre@example.com',
                'role' => 'parent',
            ])
            ->assertStatus(201);

        $mother = User::where('email', 'madre@example.com')->first();
        $motherToken = JWTAuth::fromUser($mother);

        $this->authedApi($motherToken, $owner['tenantId'])
            ->postJson('/api/v1/planning/boards', ['name' => 'Plan'])
            ->assertStatus(403);
    }
}
