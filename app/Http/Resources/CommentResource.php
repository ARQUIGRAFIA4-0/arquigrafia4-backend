<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'image_id'     => $this->image_id,
            'content'    => $this->is_deleted ? null : $this->content,
            'is_deleted' => $this->is_deleted,
            'is_edited'  => !is_null($this->edited_at),
            'edited_at'  => $this->edited_at,
            'created_at' => $this->created_at,
            'user'       => new UserResource($this->whenLoaded('user')),
            'replies'    => CommentResource::collection($this->whenLoaded('replies')),
            'replies_count' => $this->replies_count ?? $this->replies()->count(),
            // preparado para likes — retorna 0 até implementar
            'likes_count' => $this->likes_count ?? $this->likes()->count(),
            'liked_by_me' => $this->likes()
                ->where('user_id', $request->user()?->id)
                ->exists(),
        ];
    }
}
