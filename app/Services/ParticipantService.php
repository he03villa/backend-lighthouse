<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ParticipantService
{
    public function list(): Collection
    {
        return Participant::query()->with('guardians', 'groups')->get();
    }

    public function create(array $data): Participant
    {
        app(TenantLimits::class)->assertWithinLimit('max_participants', Participant::count());

        $guardians = $data['guardians'] ?? [];
        unset($data['guardians']);

        return DB::transaction(function () use ($data, $guardians) {
            $participant = Participant::create($data);

            foreach ($guardians as $guardian) {
                $user = User::firstOrCreate(
                    ['email' => $guardian['email']],
                    [
                        'name' => $guardian['name'] ?? Str::before($guardian['email'], '@'),
                        'password' => Hash::make(Str::password(20)),
                    ],
                );

                $this->addGuardian($participant, $user, $guardian);
            }

            return $participant->load('guardians', 'groups');
        });
    }

    public function update(Participant $participant, array $data): Participant
    {
        $participant->update($data);

        return $participant->load('guardians', 'groups');
    }

    public function delete(Participant $participant): void
    {
        $participant->delete();
    }

    public function addGuardian(Participant $participant, User $user, array $data): Participant
    {
        $participant->guardians()->syncWithoutDetaching([
            $user->id => [
                'id' => (string) Str::uuid(),
                'relationship' => $data['relationship'] ?? null,
                'is_primary' => $data['is_primary'] ?? false,
                'permissions' => $data['permissions'] ?? null,
            ],
        ]);

        $tenant = app(TenantContext::class)->current();

        if ($tenant) {
            app(TenantService::class)->addMemberWithRole($user, $tenant, 'parent');
        }

        return $participant->load('guardians', 'groups');
    }

    public function removeGuardian(Participant $participant, User $user): void
    {
        $participant->guardians()->detach($user->id);
    }
}
