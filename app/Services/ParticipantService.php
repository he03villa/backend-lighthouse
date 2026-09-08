<?php

namespace App\Services;

use App\Jobs\SendGuardianInvitationJob;
use App\Models\Participant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ParticipantService
{
    public function list()
    {
        return Participant::query()->with('guardians', 'groups')->paginate(20);
    }

    /**
     * @return array{participant: Participant, login: array{email: string, password: string}|null}
     */
    public function create(array $data): array
    {
        app(TenantLimits::class)->assertWithinLimit('max_participants', Participant::count());

        $guardians = $data['guardians'] ?? [];
        $createLogin = $data['create_login'] ?? false;
        $loginEmail = $data['login_email'] ?? null;
        $loginPassword = $data['login_password'] ?? null;
        unset($data['guardians'], $data['create_login'], $data['login_email'], $data['login_password']);

        $login = null;

        $participant = DB::transaction(function () use (&$login, $data, $guardians, $createLogin, $loginEmail, $loginPassword) {
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

            if ($createLogin || $loginEmail || $loginPassword) {
                $this->assertOldEnoughForLogin($participant);
                $login = $this->createParticipantLogin($participant, $loginEmail, $loginPassword);
            }

            return $participant->load('guardians', 'groups');
        });

        return ['participant' => $participant, 'login' => $login];
    }

    public function myParticipants(User $user): Collection
    {
        $tenant = app(TenantContext::class)->current();

        if (! $tenant) {
            return collect();
        }

        if ($user->hasAnyRole(['owner', 'admin', 'coach'])) {
            return Participant::query()->with('guardians', 'groups')->get();
        }

        if ($user->hasAnyRole(['parent', 'participant'])) {
            $participantIds = $user->guardianships()->pluck('participants.id');

            return Participant::whereIn('id', $participantIds)->with('guardians', 'groups')->get();
        }

        return collect();
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

            if (! $user->invitation_token && ! $user->email_verified_at) {
                $invitation = app(AuthService::class)->createInvitation(
                    $user->email,
                    $user->name
                );

                SendGuardianInvitationJob::dispatch(
                    $user->email,
                    $participant->first_name.' '.$participant->last_name,
                    $tenant->name,
                    $invitation['token']
                );
            }
        }

        return $participant->load('guardians', 'groups');
    }

    public function removeGuardian(Participant $participant, User $user): void
    {
        $participant->guardians()->detach($user->id);
    }

    private function assertOldEnoughForLogin(Participant $participant): void
    {
        if (! $participant->birth_date) {
            abort(422, 'A birth date is required to create a participant login.');
        }

        if ($participant->birth_date->diffInYears(now()) < 14) {
            abort(422, 'Participants must be at least 14 years old to have their own login.');
        }
    }

    /**
     * @return array{email: string, password: string}|null credentials only when the password was generated here
     */
    private function createParticipantLogin(Participant $participant, ?string $email, ?string $password): ?array
    {
        $email = $email ?? Str::lower($participant->first_name).'.'.Str::lower(Str::random(4)).'@lighthouse.local';
        $generatedPassword = $password === null;
        $password ??= Str::password(20);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $participant->first_name.' '.$participant->last_name,
                'password' => Hash::make($password),
            ],
        );

        $participant->guardians()->syncWithoutDetaching([
            $user->id => [
                'id' => (string) Str::uuid(),
                'relationship' => 'self',
                'is_primary' => false,
                'permissions' => null,
            ],
        ]);

        $tenant = app(TenantContext::class)->current();
        if ($tenant) {
            app(TenantService::class)->addMemberWithRole($user, $tenant, 'participant');
        }

        return $generatedPassword ? ['email' => $email, 'password' => $password] : null;
    }
}
