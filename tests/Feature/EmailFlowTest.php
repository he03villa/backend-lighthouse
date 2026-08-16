<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NewActivities;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Helpers\TenantTestHelpers;
use Tests\TestCase;

class EmailFlowTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createProgram(string $token, string $tenantId): string
    {
        return $this->authedApi($token, $tenantId)
            ->postJson('/api/v1/programs', [
                'name' => 'Programa',
                'modules' => [
                    [
                        'name' => 'Módulo 1',
                        'activities' => [
                            ['name' => 'Actividad 1', 'type' => 'reflection'],
                        ],
                    ],
                ],
            ])
            ->assertStatus(201)
            ->json('data.id');
    }

    protected function createParticipantWithGuardian(string $token, string $tenantId, string $guardianEmail): string
    {
        return $this->authedApi($token, $tenantId)
            ->postJson('/api/v1/participants', [
                'first_name' => 'Niño',
                'last_name' => 'Participante',
                'guardians' => [
                    ['email' => $guardianEmail, 'name' => 'Madre', 'relationship' => 'madre', 'is_primary' => true],
                ],
            ])
            ->assertStatus(201)
            ->json('data.id');
    }

    public function test_enrollment_creation_notifies_guardian(): void
    {
        Notification::fake();

        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);
        $programId = $this->createProgram($owner['token'], $owner['tenantId']);
        $participantId = $this->createParticipantWithGuardian($owner['token'], $owner['tenantId'], 'madre@example.com');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/programs/'.$programId.'/publish')
            ->assertStatus(200);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/enrollments', [
                'participant_id' => $participantId,
                'program_id' => $programId,
            ])
            ->assertStatus(201);

        $guardian = User::where('email', 'madre@example.com')->firstOrFail();

        Notification::assertSentTo($guardian, NewActivities::class);
    }

    public function test_program_publish_notifies_guardians_of_enrolled_participants(): void
    {
        Notification::fake();

        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);
        $programId = $this->createProgram($owner['token'], $owner['tenantId']);
        $participantId = $this->createParticipantWithGuardian($owner['token'], $owner['tenantId'], 'madre@example.com');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/programs/'.$programId.'/publish')
            ->assertStatus(200);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/enrollments', [
                'participant_id' => $participantId,
                'program_id' => $programId,
            ])
            ->assertStatus(201);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/programs/'.$programId.'/publish')
            ->assertStatus(200);

        $guardian = User::where('email', 'madre@example.com')->firstOrFail();

        Notification::assertSentTo($guardian, NewActivities::class, 2);
    }
}
