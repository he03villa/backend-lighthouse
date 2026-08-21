<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Helpers\TenantTestHelpers;
use Tests\TestCase;

class ParticipantFlowTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_sees_all_participants_in_my_participants(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        foreach (['Uno', 'Dos'] as $lastName) {
            $this->authedApi($owner['token'], $owner['tenantId'])
                ->postJson('/api/v1/participants', ['first_name' => 'Nino', 'last_name' => $lastName])
                ->assertStatus(201);
        }

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/participants/my-participants')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_parent_sees_only_their_participants(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/participants', [
                'first_name' => 'Hijo',
                'last_name' => 'Propio',
                'guardians' => [
                    ['email' => 'madre@example.com', 'name' => 'Madre', 'relationship' => 'madre'],
                ],
            ])
            ->assertStatus(201);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/participants', ['first_name' => 'Otro', 'last_name' => 'Nino'])
            ->assertStatus(201);

        $mother = User::where('email', 'madre@example.com')->first();

        $this->authedApi(JWTAuth::fromUser($mother), $owner['tenantId'])
            ->getJson('/api/v1/participants/my-participants')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'Hijo Propio');
    }

    public function test_participant_login_sees_only_self(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/participants', [
                'first_name' => 'Nino',
                'last_name' => 'Mayor',
                'birth_date' => '2008-05-10',
                'create_login' => true,
                'login_email' => 'nino@example.com',
                'login_password' => 'secret1234',
            ])
            ->assertStatus(201);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/participants', ['first_name' => 'Otro', 'last_name' => 'Nino'])
            ->assertStatus(201);

        $participantUser = User::where('email', 'nino@example.com')->first();

        $this->authedApi(JWTAuth::fromUser($participantUser), $owner['tenantId'])
            ->getJson('/api/v1/participants/my-participants')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'Nino Mayor');
    }

    public function test_create_login_generates_credentials_and_returns_them(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $response = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/participants', [
                'first_name' => 'Nina',
                'last_name' => 'Mayor',
                'birth_date' => '2008-05-10',
                'create_login' => true,
            ])
            ->assertStatus(201);

        $loginEmail = $response->json('data.login.email');
        $loginPassword = $response->json('data.login.password');

        $this->assertNotNull($loginEmail);
        $this->assertStringEndsWith('@lighthouse.local', $loginEmail);
        $this->assertNotEmpty($loginPassword);

        $user = User::where('email', $loginEmail)->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check($loginPassword, $user->password));

        $participantId = $response->json('data.id');
        $this->assertNotNull($participantId);
        $this->assertDatabaseHas('participant_guardians', [
            'participant_id' => $participantId,
            'user_id' => $user->id,
            'relationship' => 'self',
        ]);
    }

    public function test_create_login_does_not_return_password_when_provided(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/participants', [
                'first_name' => 'Nino',
                'last_name' => 'Mayor',
                'birth_date' => '2008-05-10',
                'create_login' => true,
                'login_email' => 'nino@example.com',
                'login_password' => 'secret1234',
            ])
            ->assertStatus(201)
            ->assertJsonMissingPath('data.login');
    }

    public function test_create_login_rejected_for_underage_participant(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/participants', [
                'first_name' => 'Nino',
                'last_name' => 'Menor',
                'birth_date' => '2015-01-01',
                'create_login' => true,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_create_login_requires_birth_date(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/participants', [
                'first_name' => 'Sin',
                'last_name' => 'Fecha',
                'create_login' => true,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_login_email_must_be_unique(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/participants', [
                'first_name' => 'Duplicado',
                'last_name' => 'Email',
                'birth_date' => '2008-05-10',
                'create_login' => true,
                'login_email' => 'owner@example.com',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['login_email']);
    }
}
