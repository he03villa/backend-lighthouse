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

class JournalFlowTest extends TestCase
{
    use RefreshDatabase, TenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_creates_and_lists_journal_entries(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/journal', ['content' => 'Primera entrada'])
            ->assertStatus(201)
            ->assertJsonPath('data.content', 'Primera entrada')
            ->assertJsonPath('data.visibility', 'private');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->getJson('/api/v1/journal')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Primera entrada');
    }

    public function test_owner_can_update_and_delete_own_entry(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $entryId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/journal', ['content' => 'Borrador'])
            ->json('data.id');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->patchJson('/api/v1/journal/'.$entryId, ['content' => 'Corregido', 'visibility' => 'public'])
            ->assertStatus(200)
            ->assertJsonPath('data.content', 'Corregido')
            ->assertJsonPath('data.visibility', 'public');

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->deleteJson('/api/v1/journal/'.$entryId)
            ->assertStatus(200);

        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_parent_cannot_edit_others_entry(): void
    {
        $owner = $this->registerWithTenant(['email' => 'owner@example.com']);

        $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/tenants/'.$owner['tenantId'].'/members', [
                'email' => 'madre@example.com',
                'role' => 'parent',
            ])
            ->assertStatus(201);

        $entryId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/journal', ['content' => 'Del coach'])
            ->json('data.id');

        $mother = User::where('email', 'madre@example.com')->first();
        $motherToken = JWTAuth::fromUser($mother);

        $this->authedApi($motherToken, $owner['tenantId'])
            ->patchJson('/api/v1/journal/'.$entryId, ['content' => 'Edición no permitida'])
            ->assertStatus(403);
    }

    public function test_parent_only_sees_shared_entries_of_their_participant(): void
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
            ->postJson('/api/v1/journal', ['content' => 'Privada', 'participant_id' => $participant->id, 'visibility' => 'private'])
            ->assertStatus(201);

        $sharedEntryId = $this->authedApi($owner['token'], $owner['tenantId'])
            ->postJson('/api/v1/journal', ['content' => 'Compartida con familia', 'participant_id' => $participant->id, 'visibility' => 'shared_family'])
            ->assertStatus(201)
            ->json('data.id');

        $motherToken = JWTAuth::fromUser($mother);

        $this->authedApi($motherToken, $owner['tenantId'])
            ->getJson('/api/v1/journal')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Compartida con familia');

        $this->authedApi($motherToken, $owner['tenantId'])
            ->getJson('/api/v1/journal/'.$sharedEntryId)
            ->assertStatus(200);
    }
}
