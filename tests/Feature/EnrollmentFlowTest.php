<?php

namespace Tests\Feature;

use App\Enums\FieldNoteVisibility;
use App\Models\FieldNote;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Helpers\TenantTestHelpers;
use Tests\TestCase;

class EnrollmentFlowTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createParticipant(string $token, string $tenantId, string $guardianEmail): string
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

    public function test_full_enrollment_and_progress_flow(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $programId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/programs', [
                'name' => 'Programa',
                'modules' => [
                    [
                        'name' => 'Módulo',
                        'activities' => [
                            ['name' => 'Actividad 1', 'type' => 'reflection'],
                            ['name' => 'Actividad 2', 'type' => 'completion'],
                        ],
                    ],
                ],
            ])
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/programs/'.$programId.'/publish')
            ->assertStatus(200);

        $participantId = $this->createParticipant($owner['token'], $owner['tenantId'], 'madre@example.com');

        $guardian = User::where('email', 'madre@example.com')->first();
        $this->assertNotNull($guardian);
        $this->assertTrue($guardian->tenants()->where('tenants.id', $owner['tenantId'])->exists());
        $guardianToken = JWTAuth::fromUser($guardian);

        $enrollment = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/enrollments', [
                'participant_id' => $participantId,
                'program_id' => $programId,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.progress.total_activities', 2)
            ->assertJsonPath('data.progress.percentage', 0)
            ->json('data');

        $activities = $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/programs/'.$programId)
            ->json('data.modules.0.activities');

        $submission = $this->authedApi($guardianToken, $owner['tenantId'])
            ->postJson('/api/v1/activities/'.$activities[0]['id'].'/submit', [
                'enrollment_id' => $enrollment['id'],
                'evidence_type' => 'text',
                'content' => 'Mi evidencia',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonCount(1, 'data.evidences')
            ->json('data');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->patchJson('/api/v1/submissions/'.$submission['id'].'/review', [
                'status' => 'approved',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/enrollments/'.$enrollment['id'].'/progress')
            ->assertStatus(200)
            ->assertJsonPath('data.completed_activities', 1)
            ->assertJsonPath('data.total_activities', 2)
            ->assertJsonPath('data.percentage', 50);
    }

    public function test_image_evidence_and_review_creates_private_field_note(): void
    {
        Storage::fake('public');

        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $programId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/programs', [
                'name' => 'Programa',
                'modules' => [['name' => 'Módulo', 'activities' => [['name' => 'Actividad', 'type' => 'upload']]]],
            ])
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/programs/'.$programId.'/publish')
            ->assertStatus(200);

        $participantId = $this->createParticipant($owner['token'], $owner['tenantId'], 'madre@example.com');

        $enrollment = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/enrollments', [
                'participant_id' => $participantId,
                'program_id' => $programId,
            ])
            ->assertStatus(201)
            ->json('data');

        $guardianToken = JWTAuth::fromUser(User::where('email', 'madre@example.com')->first());

        $activityId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/programs/'.$programId)
            ->json('data.modules.0.activities.0.id');

        $submission = $this->authedApi($guardianToken, $owner['tenantId'])
            ->postJson('/api/v1/activities/'.$activityId.'/submit', [
                'enrollment_id' => $enrollment['id'],
                'evidence_type' => 'image',
                'evidence_file' => UploadedFile::fake()->image('evidencia.jpg'),
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.evidences.0.type', 'image')
            ->json('data');

        $evidenceUrl = $submission['evidences'][0]['content'];
        $this->assertStringStartsWith('/storage/evidences/', $evidenceUrl);

        Storage::disk('public')->assertExists('evidences/'.$owner['tenantId'].'/'.basename($evidenceUrl));

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->patchJson('/api/v1/submissions/'.$submission['id'].'/review', [
                'status' => 'approved',
                'observation' => 'Muy bien hecho.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $note = FieldNote::withoutGlobalScope('tenant')->first();

        $this->assertNotNull($note);
        $this->assertSame($submission['id'], $note->activity_submission_id);
        $this->assertSame(FieldNoteVisibility::Private, $note->visibility);
        $this->assertSame('Muy bien hecho.', $note->content);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/enrollments/'.$enrollment['id'].'/progress')
            ->assertStatus(200)
            ->assertJsonPath('data.percentage', 100);
    }

    public function test_guardian_cannot_review_evidence(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $programId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/programs', [
                'name' => 'Programa',
                'modules' => [['name' => 'Módulo', 'activities' => [['name' => 'Actividad']]]],
            ])
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/programs/'.$programId.'/publish')
            ->assertStatus(200);

        $participantId = $this->createParticipant($owner['token'], $owner['tenantId'], 'madre@example.com');
        $guardianToken = JWTAuth::fromUser(User::where('email', 'madre@example.com')->first());

        $enrollment = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/enrollments', [
                'participant_id' => $participantId,
                'program_id' => $programId,
            ])
            ->json('data');

        $activityId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/programs/'.$programId)
            ->json('data.modules.0.activities.0.id');

        $submissionId = $this->authedApi($guardianToken, $owner['tenantId'])
            ->postJson('/api/v1/activities/'.$activityId.'/submit', [
                'enrollment_id' => $enrollment['id'],
                'content' => 'Evidencia',
            ])
            ->json('data.id');

        $this->authedApi($guardianToken, $owner['tenantId'])
            ->patchJson('/api/v1/submissions/'.$submissionId.'/review', ['status' => 'approved'])
            ->assertStatus(403);
    }

    public function test_cannot_enroll_in_unpublished_program(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $programId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/programs', ['name' => 'Programa'])
            ->json('data.id');

        $participantId = $this->createParticipant($owner['token'], $owner['tenantId'], 'madre@example.com');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/enrollments', [
                'participant_id' => $participantId,
                'program_id' => $programId,
            ])
            ->assertStatus(422);
    }

    public function test_cannot_enroll_in_program_from_another_tenant(): void
    {
        $ownerA = $this->registerWithTenant(['email' => 'a@example.com', 'tenant_name' => 'Club A']);
        $ownerB = $this->registerWithTenant(['email' => 'b@example.com', 'tenant_name' => 'Club B']);

        $programId = $this->authedApi($ownerA['token'], $ownerA['tenantId'])
            ->postJson('/api/v1/programs', ['name' => 'Programa A'])
            ->json('data.id');

        $this->authedApi($ownerA['token'], $ownerA['tenantId'])
            ->postJson('/api/v1/programs/'.$programId.'/publish')
            ->assertStatus(200);

        $participantId = $this->createParticipant($ownerB['token'], $ownerB['tenantId'], 'madre@example.com');

        $this->authedApi($ownerB['token'], $ownerB['tenantId'])
            ->postJson('/api/v1/enrollments', [
                'participant_id' => $participantId,
                'program_id' => $programId,
            ])
            ->assertStatus(422);
    }

    public function test_dropping_enrollment_marks_it_dropped(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $programId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/programs', ['name' => 'Programa'])
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/programs/'.$programId.'/publish')
            ->assertStatus(200);

        $participantId = $this->createParticipant($owner['token'], $owner['tenantId'], 'madre@example.com');

        $enrollmentId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/enrollments', [
                'participant_id' => $participantId,
                'program_id' => $programId,
            ])
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->deleteJson('/api/v1/enrollments/'.$enrollmentId)
            ->assertStatus(200);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/enrollments/'.$enrollmentId)
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'dropped');
    }
}
