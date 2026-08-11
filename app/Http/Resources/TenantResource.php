<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type?->value,
            'config' => $this->config,
            'created_at' => $this->created_at?->toISOString(),
            'is_active' => $this->whenPivotLoaded('tenant_user', fn () => (bool) $this->pivot->is_active),
        ];
    }
}
