<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForumPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'category' => $this->category,
            'pinned' => $this->pinned,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'author' => new UserResource($this->whenLoaded('author')),
            'comments_count' => $this->whenCounted('comments'),
            'reactions_count' => $this->whenCounted('reactions'),
            'user_reaction' => $this->user_reaction ?? null,
            'comments' => ForumCommentResource::collection($this->whenLoaded('comments')),
            'reactions' => ForumReactionResource::collection($this->whenLoaded('reactions')),
        ];
    }
}
