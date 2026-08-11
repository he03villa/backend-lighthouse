<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivitySubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'activity' => new ActivityResource($this->whenLoaded('activity')),
            'enrollment' => new EnrollmentResource($this->whenLoaded('enrollment')),
            'evidences' => EvidenceResource::collection($this->whenLoaded('evidences')),
            'reviewed_by' => new UserResource($this->whenLoaded('reviewedBy')),
        ];
    }
}
