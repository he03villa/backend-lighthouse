<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForumCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'created_at' => $this->created_at?->toISOString(),
            'author' => new UserResource($this->whenLoaded('author')),
            'reactions_count' => $this->whenCounted('reactions'),
            'user_reaction' => $this->user_reaction ?? null,
            'reactions' => ForumReactionResource::collection($this->whenLoaded('reactions')),
        ];
    }
}
