<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\PermissionRegistrar;

class JournalService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected PermissionRegistrar $permissions,
    ) {}

    public function list(User $user, ?string $participantId = null, ?string $from = null, ?string $to = null): LengthAwarePaginator
    {
        $query = JournalEntry::query()
            ->with(['author', 'participant'])
            ->when($participantId, fn ($q) => $q->where('participant_id', $participantId))
            ->when($from, fn ($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('entry_date', '<=', $to))
            ->latest('entry_date');

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

        return $query->paginate(20);
    }

    public function show(User $user, JournalEntry $entry): JournalEntry
    {
        $this->assertVisible($user, $entry);

        return $entry->load(['author', 'participant']);
    }

    public function create(User $author, array $data): JournalEntry
    {
        $entry = JournalEntry::create([
            'author_user_id' => $author->id,
            'participant_id' => $data['participant_id'] ?? null,
            'entry_date' => $data['entry_date'] ?? now()->toDateString(),
            'content' => $data['content'],
            'visibility' => $data['visibility'] ?? 'private',
        ]);

        return $entry->load(['author', 'participant']);
    }

    public function update(User $user, JournalEntry $entry, array $data): JournalEntry
    {
        $entry->update([
            'participant_id' => array_key_exists('participant_id', $data) ? $data['participant_id'] : $entry->participant_id,
            'entry_date' => array_key_exists('entry_date', $data) ? $data['entry_date'] : $entry->entry_date,
            'content' => array_key_exists('content', $data) ? $data['content'] : $entry->content,
            'visibility' => array_key_exists('visibility', $data) ? $data['visibility'] : $entry->visibility,
        ]);

        return $entry->load(['author', 'participant']);
    }

    public function delete(User $user, JournalEntry $entry): void
    {
        $entry->delete();
    }

    public function canStaffView(User $user): bool
    {
        $this->permissions->setPermissionsTeamId($this->tenantContext->id());

        return $user->hasRole(['owner', 'admin', 'coach']);
    }

    protected function assertAuthor(User $user, JournalEntry $entry): void
    {
        abort_unless($entry->author_user_id === $user->id, 403, 'You can only edit or delete your own journal entries.');
    }

    protected function assertVisible(User $user, JournalEntry $entry): void
    {
        if ($entry->author_user_id === $user->id || $this->canStaffView($user)) {
            return;
        }

        $visibleToGuardian = in_array($entry->visibility->value, ['shared_family', 'shared_participant', 'public'], true)
            && $entry->participant_id !== null
            && $entry->participant?->guardians()->where('users.id', $user->id)->exists();

        abort_unless($visibleToGuardian, 404, 'Journal entry not found.');
    }
}
