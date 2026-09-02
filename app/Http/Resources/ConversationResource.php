<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_group' => $this->is_group,
            'title' => $this->title,
            'created_at' => $this->created_at?->toISOString(),
            'participants' => UserResource::collection($this->whenLoaded('participants')),
            'latest_message' => MessageResource::collection($this->whenLoaded('latestMessage')),
        ];
    }
}
