<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'enrolled_at' => $this->enrolled_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'participant' => new ParticipantResource($this->whenLoaded('participant')),
            'program' => new ProgramResource($this->whenLoaded('program')),
            'progress' => new ProgressResource($this->whenLoaded('progressRecord')),
        ];
    }
}
