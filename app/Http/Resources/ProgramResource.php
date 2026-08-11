<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'age_group' => $this->age_group,
            'duration_weeks' => $this->duration_weeks,
            'is_published' => (bool) $this->is_published,
            'thumbnail' => $this->thumbnail,
            'config' => $this->config,
            'created_at' => $this->created_at?->toISOString(),
            'modules' => ModuleResource::collection($this->whenLoaded('modules')),
        ];
    }
}
