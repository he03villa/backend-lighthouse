<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Helpers\TenantTestHelpers;
use Tests\TestCase;

class FieldNoteFlowTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_creates_and_lists_field_notes(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);
        $participantId = $this->createParticipant($owner['token'], $owner['tenantId']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/field-notes', [
                'participant_id' => $participantId,
                'content' => 'Observación de sesión',
                'visibility' => 'private',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.content', 'Observación de sesión')
            ->assertJsonPath('data.visibility', 'private');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/field-notes')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Observación de sesión');
    }

    public function test_owner_can_update_and_delete_own_field_note(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);
        $participantId = $this->createParticipant($owner['token'], $owner['tenantId']);

        $noteId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/field-notes', [
                'participant_id' => $participantId,
                'content' => 'Borrador',
            ])
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->patchJson('/api/v1/field-notes/'.$noteId, ['content' => 'Corregido', 'visibility' => 'shared_family'])
            ->assertStatus(200)
            ->assertJsonPath('data.content', 'Corregido')
            ->assertJsonPath('data.visibility', 'shared_family');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->deleteJson('/api/v1/field-notes/'.$noteId)
            ->assertStatus(200);

        $this->assertDatabaseCount('field_notes', 0);
    }

    public function test_parent_cannot_edit_others_field_note(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);
        $participantId = $this->createParticipant($owner['token'], $owner['tenantId']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/tenants/'.$owner['tenantId'].'/members', [
                'email' => 'madre@example.com',
                'role' => 'parent',
            ])
            ->assertStatus(201);

        $noteId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/field-notes', [
                'participant_id' => $participantId,
                'content' => 'Del coach',
            ])
            ->json('data.id');

        $mother = User::where('email', 'madre@example.com')->first();
        $motherToken = JWTAuth::fromUser($mother);

        $this->authedApi($motherToken, $owner['tenantId'])
            ->patchJson('/api/v1/field-notes/'.$noteId, ['content' => 'Edición no permitida'])
            ->assertStatus(403);
    }

    public function test_parent_only_sees_shared_field_notes_of_their_participant(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $participant = Participant::create([
            'tenant_id' => $owner['tenantId'],
            'first_name' => 'Nino',
            'last_name' => 'Participante',
        ]);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/tenants/'.$owner['tenantId'].'/members', [
                'email' => 'madre@example.com',
                'role' => 'parent',
            ])
            ->assertStatus(201);

        $mother = User::where('email', 'madre@example.com')->first();
        $participant->guardians()->syncWithoutDetaching([
            $mother->id => ['id' => (string) Str::uuid(), 'relationship' => 'madre', 'is_primary' => true],
        ]);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/field-notes', [
                'participant_id' => $participant->id,
                'content' => 'Privada',
                'visibility' => 'private',
            ])
            ->assertStatus(201);

        $sharedNoteId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/field-notes', [
                'participant_id' => $participant->id,
                'content' => 'Compartida con familia',
                'visibility' => 'shared_family',
            ])
            ->assertStatus(201)
            ->json('data.id');

        $motherToken = JWTAuth::fromUser($mother);

        $this->authedApi($motherToken, $owner['tenantId'])
            ->getJson('/api/v1/field-notes')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Compartida con familia');

        $this->authedApi($motherToken, $owner['tenantId'])
            ->getJson('/api/v1/field-notes/'.$sharedNoteId)
            ->assertStatus(200);
    }

    public function test_field_note_is_scoped_to_tenant(): void
    {
        $ownerA = $this->registerWithTenant(['email' => 'a@example.com']);
        $ownerB = $this->registerWithTenant(['email' => 'b@example.com']);

        $participantId = $this->createParticipant($ownerA['token'], $ownerA['tenantId']);

        $noteId = $this->authedApi($ownerA['token'], $ownerA['tenantId'])
            ->postJson('/api/v1/field-notes', [
                'participant_id' => $participantId,
                'content' => 'Solo de A',
            ])
            ->assertStatus(201)
            ->json('data.id');

        $this->authedApi($ownerB['token'], $ownerB['tenantId'])
            ->getJson('/api/v1/field-notes/'.$noteId)
            ->assertStatus(404);

        $this->authedApi($ownerB['token'], $ownerB['tenantId'])
            ->deleteJson('/api/v1/field-notes/'.$noteId)
            ->assertStatus(404);
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
