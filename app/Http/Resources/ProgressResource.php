<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'enrollment_id' => $this->enrollment_id,
            'percentage' => round((float) $this->percentage, 2),
            'completed_activities' => (int) $this->completed_activities,
            'total_activities' => (int) $this->total_activities,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
