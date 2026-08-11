<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'joined_at' => $this->whenPivotLoaded('tenant_user', fn () => $this->pivot->joined_at?->toISOString()),
            'is_active' => $this->whenPivotLoaded('tenant_user', fn () => (bool) $this->pivot->is_active),
        ];
    }
}
