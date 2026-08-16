<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'participant_id' => $this->participant_id,
            'entry_date' => $this->entry_date?->toDateString(),
            'content' => $this->content,
            'visibility' => $this->visibility?->value,
            'author' => new UserResource($this->whenLoaded('author')),
            'participant' => new ParticipantResource($this->whenLoaded('participant')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
