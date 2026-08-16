<?php

namespace App\Models;

use App\Enums\JournalPrivacy;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'author_user_id', 'participant_id', 'entry_date', 'content', 'visibility'])]
class JournalEntry extends Model
{
    use BelongsToTenant, HasUuids;

    protected function casts(): array
    {
        return [
            'visibility' => JournalPrivacy::class,
            'entry_date' => 'date',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
