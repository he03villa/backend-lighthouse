<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => trim($this->first_name.' '.$this->last_name),
            'birth_date' => $this->birth_date?->toDateString(),
            'avatar' => $this->avatar,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'guardians' => GuardianResource::collection($this->whenLoaded('guardians')),
            'groups' => GroupResource::collection($this->whenLoaded('groups')),
        ];
    }
}
