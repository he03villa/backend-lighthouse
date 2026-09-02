<?php

namespace App\Services;

use App\Enums\FieldNoteVisibility;
use App\Models\FieldNote;
use App\Models\Participant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\PermissionRegistrar;

class FieldNoteService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected PermissionRegistrar $permissions,
    ) {}

    public function list(User $user, ?Participant $participant = null): Collection
    {
        $query = FieldNote::query()
            ->with('author', 'participant', 'submission.activity')
            ->when($participant, fn ($q) => $q->where('participant_id', $participant->id));

        if (! $this->canStaffView($user)) {
            $query->where(function ($q) use ($user) {
                $q->where('author_user_id', $user->id)
                    ->orWhere(function ($sub) use ($user) {
                        $sub->whereIn('visibility', ['shared_family', 'shared_participant', 'public'])
                            ->whereHas('participant', function ($p) use ($user) {
                                $p->whereHas('guardians', fn ($g) => $g->where('users.id', $user->id));
                            });
                    });
            });
        }

        return $query->latest('session_date')->get();
    }

    public function show(User $user, FieldNote $note): FieldNote
    {
        $this->assertVisible($user, $note);

        return $note->load('author', 'participant', 'submission.activity');
    }

    public function create(User $author, array $data): FieldNote
    {
        return FieldNote::create([
            'author_user_id' => $author->id,
            'participant_id' => $data['participant_id'],
            'activity_submission_id' => $data['activity_submission_id'] ?? null,
            'session_date' => $data['session_date'] ?? now()->toDateString(),
            'content' => $data['content'],
            'visibility' => FieldNoteVisibility::from($data['visibility'] ?? 'private'),
        ])->load('author');
    }

    public function update(User $user, FieldNote $note, array $data): FieldNote
    {
        $this->assertAuthor($user, $note);

        $note->update([
            'participant_id' => array_key_exists('participant_id', $data) ? $data['participant_id'] : $note->participant_id,
            'session_date' => array_key_exists('session_date', $data) ? $data['session_date'] : $note->session_date,
            'content' => array_key_exists('content', $data) ? $data['content'] : $note->content,
            'visibility' => array_key_exists('visibility', $data) ? FieldNoteVisibility::from($data['visibility']) : $note->visibility,
        ]);

        return $note->load('author', 'participant');
    }

    public function delete(User $user, FieldNote $note): void
    {
        $this->assertAuthor($user, $note);

        $note->delete();
    }

    public function canStaffView(User $user): bool
    {
        $this->permissions->setPermissionsTeamId($this->tenantContext->id());

        return $user->hasRole(['owner', 'admin', 'coach']);
    }

    protected function assertAuthor(User $user, FieldNote $note): void
    {
        abort_unless($note->author_user_id === $user->id, 403, 'You can only edit or delete your own field notes.');
    }

    protected function assertVisible(User $user, FieldNote $note): void
    {
        if ($note->author_user_id === $user->id || $this->canStaffView($user)) {
            return;
        }

        $visibleToGuardian = in_array($note->visibility->value, ['shared_family', 'shared_participant', 'public'], true)
            && $note->participant_id !== null
            && $note->participant?->guardians()->where('users.id', $user->id)->exists();

        abort_unless($visibleToGuardian, 404, 'Field note not found.');
    }
}
