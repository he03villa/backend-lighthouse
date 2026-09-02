<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Module;
use App\Models\Participant;
use App\Models\Program;
use App\Services\AiService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use Tests\Helpers\TenantTestHelpers;
use Tests\TestCase;

class AiFlowTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_coach_can_summarize_progress(): void
    {
        $owner = $this->registerWithTenant(['email' => 'coach@example.com']);
        $participantId = $this->createParticipant($owner['token'], $owner['tenantId']);

        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    ['message' => ['content' => 'Resumen: El participante ha avanzado bien en el programa.']],
                ],
            ]),
        ]);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/ai/summarize-progress', [
                'participant_id' => $participantId,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.summary', 'Resumen: El participante ha avanzado bien en el programa.');
    }

    public function test_returns_503_when_ai_service_fails(): void
    {
        $owner = $this->registerWithTenant(['email' => 'coach@example.com']);
        $participantId = $this->createParticipant($owner['token'], $owner['tenantId']);

        OpenAI::fake([
            new \Exception('AI service temporarily unavailable'),
            new \Exception('AI service temporarily unavailable'),
        ]);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/ai/summarize-progress', [
                'participant_id' => $participantId,
            ])
            ->assertStatus(503);
    }

    public function test_parent_cannot_use_ai_assistant_but_can_explain(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);
        $program = Program::create(['tenant_id' => $owner['tenantId'], 'name' => 'Programa A', 'description' => 'Desc']);
        $module = Module::create(['tenant_id' => $owner['tenantId'], 'program_id' => $program->id, 'name' => 'Modulo 1', 'description' => 'Desc']);
        $activity = Activity::create(['tenant_id' => $owner['tenantId'], 'module_id' => $module->id, 'name' => 'Act 1', 'description' => 'Desc', 'objective' => 'Obj', 'level' => 'beginner']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/tenants/'.$owner['tenantId'].'/members', [
                'email' => 'parent@example.com',
                'role' => 'parent',
            ])
            ->assertStatus(201);

        $parent = \App\Models\User::where('email', 'parent@example.com')->first();
        $parentToken = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::fromUser($parent);

        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    ['message' => ['content' => 'Esta actividad ensena habilidades basicas.']],
                ],
            ]),
        ]);

        $this->authedApi($parentToken, $owner['tenantId'])
            ->postJson('/api/v1/ai/summarize-progress', ['participant_id' => '00000000-0000-0000-0000-000000000000'])
            ->assertStatus(403);

        $this->authedApi($parentToken, $owner['tenantId'])
            ->postJson('/api/v1/ai/explain-activity', ['activity_id' => $activity->id])
            ->assertStatus(200)
            ->assertJsonPath('data.explanation', 'Esta actividad ensena habilidades basicas.');
    }

    public function test_generate_draft_creates_goal_draft(): void
    {
        $owner = $this->registerWithTenant(['email' => 'coach@example.com']);

        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    ['message' => ['content' => '{"title":"Meta de lectura","description":"Mejorar comprension lectora","target_date":"2026-12-31"}']],
                ],
            ]),
        ]);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/ai/generate-draft', [
                'type' => 'goal',
                'context' => ['participant_name' => 'Juan'],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.draft.title', 'Meta de lectura');
    }

    public function test_rate_limiting_is_applied(): void
    {
        $owner = $this->registerWithTenant(['email' => 'coach@example.com']);
        $participantId = $this->createParticipant($owner['token'], $owner['tenantId']);

        $fakeResponse = CreateResponse::fake([
            'choices' => [
                ['message' => ['content' => 'OK']],
            ],
        ]);

        OpenAI::fake(array_fill(0, 30, $fakeResponse));

        for ($i = 0; $i < 10; $i++) {
            $this->authedApi($owner['token'], $owner['tenantId'])
                ->postJson('/api/v1/ai/summarize-progress', [
                    'participant_id' => $participantId,
                ])
                ->assertStatus(200);
        }

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/ai/summarize-progress', [
                'participant_id' => $participantId,
            ])
            ->assertStatus(429);
    }

    protected function createParticipant(string $token, string $tenantId): string
    {
        return $this->authedApi($token, $tenantId)
            ->postJson('/api/v1/participants', [
                'first_name' => 'Nino',
                'last_name' => 'Participante',
            ])
            ->assertStatus(201)
            ->json('data.id');
    }
}
