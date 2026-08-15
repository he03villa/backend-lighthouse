<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Helpers\TenantTestHelpers;
use Tests\TestCase;

class ProgramFlowTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_can_create_program_with_modules_and_activities(): void
    {
        $result = $this->registerWithTenant();

        $response = $this->authedApi($result['token'], $result['tenantId'])
            ->postJson('/api/v1/programs', [
                'name' => 'Programa Habilidades',
                'description' => 'Entrenamiento socioemocional',
                'age_group' => '8-14',
                'duration_weeks' => 12,
                'modules' => [
                    [
                        'name' => 'Identificación emocional',
                        'activities' => [
                            ['name' => 'Rueda de emociones', 'type' => 'reflection'],
                            ['name' => 'Diario', 'type' => 'completion'],
                        ],
                    ],
                    [
                        'name' => 'Regulación',
                        'activities' => [
                            ['name' => 'Respiración', 'type' => 'completion'],
                        ],
                    ],
                ],
            ])
            ->assertStatus(201);

        $programId = $response->json('data.id');

        $this->assertCount(2, $response->json('data.modules'));
        $this->assertCount(2, $response->json('data.modules.0.activities'));
        $this->assertCount(1, $response->json('data.modules.1.activities'));

        $this->assertDatabaseHas('programs', ['id' => $programId, 'name' => 'Programa Habilidades']);
    }

    public function test_program_list_and_show(): void
    {
        $result = $this->registerWithTenant();

        $programId = $this->authedApi($result['token'], $result['tenantId'])
            ->postJson('/api/v1/programs', [
                'name' => 'Programa A',
                'modules' => [
                    ['name' => 'Módulo 1', 'activities' => [['name' => 'Actividad 1']]],
                ],
            ])
            ->json('data.id');

        $this->authedApi($result['token'], $result['tenantId'])
            ->getJson('/api/v1/programs')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->authedApi($result['token'], $result['tenantId'])
            ->getJson('/api/v1/programs/'.$programId)
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Programa A')
            ->assertJsonPath('data.modules.0.activities.0.name', 'Actividad 1');
    }

    public function test_list_filters_published_programs(): void
    {
        $result = $this->registerWithTenant();

        $api = fn () => $this->authedApi($result['token'], $result['tenantId']);

        $api()->postJson('/api/v1/programs', ['name' => 'Publicado']);
        $publishedId = $api()->postJson('/api/v1/programs', ['name' => 'A Publicar'])
            ->json('data.id');

        $api()->postJson('/api/v1/programs/'.$publishedId.'/publish')->assertStatus(200);

        $api()->getJson('/api/v1/programs?published=true')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'A Publicar');

        $api()->getJson('/api/v1/programs')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $api()->getJson('/api/v1/programs?published=')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_publish_and_unpublish_program(): void
    {
        $result = $this->registerWithTenant();

        $programId = $this->authedApi($result['token'], $result['tenantId'])
            ->postJson('/api/v1/programs', ['name' => 'Programa B'])
            ->json('data.id');

        $this->authedApi($result['token'], $result['tenantId'])
            ->postJson('/api/v1/programs/'.$programId.'/publish')
            ->assertStatus(200)
            ->assertJsonPath('data.is_published', true);

        $this->authedApi($result['token'], $result['tenantId'])
            ->postJson('/api/v1/programs/'.$programId.'/unpublish')
            ->assertStatus(200)
            ->assertJsonPath('data.is_published', false);
    }

    public function test_programs_are_isolated_per_tenant(): void
    {
        $resultA = $this->registerWithTenant(['email' => 'a@example.com', 'tenant_name' => 'Club A']);
        $resultB = $this->registerWithTenant(['email' => 'b@example.com', 'tenant_name' => 'Club B']);

        $programId = $this->authedApi($resultA['token'], $resultA['tenantId'])
            ->postJson('/api/v1/programs', ['name' => 'Programa Secreto'])
            ->json('data.id');

        $this->authedApi($resultB['token'], $resultB['tenantId'])
            ->getJson('/api/v1/programs/'.$programId)
            ->assertStatus(404);

        $this->authedApi($resultB['token'], $resultB['tenantId'])
            ->getJson('/api/v1/programs')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_unauthorized_role_cannot_create_program(): void
    {
        $result = $this->registerWithTenant();

        $parentId = $this->authedApi($result['token'], $result['tenantId'])
            ->postJson('/api/v1/tenants/'.$result['tenantId'].'/members', [
                'email' => 'parent@example.com',
                'role' => 'parent',
            ])
            ->json('data.id');

        $parent = User::findOrFail($parentId);
        $parentToken = JWTAuth::fromUser($parent);

        $this->authedApi($parentToken, $result['tenantId'])
            ->postJson('/api/v1/programs', ['name' => 'No debería pasar'])
            ->assertStatus(403);
    }

    public function test_owner_can_upload_program_thumbnail(): void
    {
        Storage::fake('public');

        $result = $this->registerWithTenant();

        $this->authedApi($result['token'], $result['tenantId'])
            ->post('/api/v1/programs/thumbnail', [
                'thumbnail' => UploadedFile::fake()->image('thumb.jpg', 200, 200),
            ], ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.url', fn (string $url) => str_contains($url, '/storage/programs/'));

        $this->assertNotEmpty(Storage::disk('public')->allFiles('programs/'.$result['tenantId']));
    }

    public function test_unauthorized_role_cannot_upload_thumbnail(): void
    {
        $result = $this->registerWithTenant();

        $parentId = $this->authedApi($result['token'], $result['tenantId'])
            ->postJson('/api/v1/tenants/'.$result['tenantId'].'/members', [
                'email' => 'parent-thumb@example.com',
                'role' => 'parent',
            ])
            ->json('data.id');

        $parent = User::findOrFail($parentId);
        $parentToken = JWTAuth::fromUser($parent);

        $this->authedApi($parentToken, $result['tenantId'])
            ->post('/api/v1/programs/thumbnail', [
                'thumbnail' => UploadedFile::fake()->image('thumb.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(403);
    }

    public function test_thumbnail_requires_an_image_file(): void
    {
        $result = $this->registerWithTenant();

        $this->authedApi($result['token'], $result['tenantId'])
            ->post('/api/v1/programs/thumbnail', [], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }
}
