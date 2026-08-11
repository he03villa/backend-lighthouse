<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'visibility' => $this->visibility?->value,
            'session_date' => $this->session_date?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),
            'author' => new UserResource($this->whenLoaded('author')),
            'participant' => new ParticipantResource($this->whenLoaded('participant')),
            'submission' => new ActivitySubmissionResource($this->whenLoaded('submission')),
        ];
    }
}
