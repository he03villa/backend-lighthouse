<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'relationship' => $this->whenPivotLoaded('participant_guardians', fn () => $this->pivot->relationship),
            'is_primary' => $this->whenPivotLoaded('participant_guardians', fn () => (bool) $this->pivot->is_primary),
            'permissions' => $this->whenPivotLoaded('participant_guardians', fn () => $this->pivot->permissions),
        ];
    }
}
