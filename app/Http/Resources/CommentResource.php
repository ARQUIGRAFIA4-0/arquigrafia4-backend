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
            'content'    => $this->is_deleted ? null : $this->content,
            'is_deleted' => $this->is_deleted,
            'is_edited'  => !is_null($this->edited_at),
            'edited_at'  => $this->edited_at,
            'created_at' => $this->created_at,
            'user'       => new UserResource($this->whenLoaded('user')),
            'replies'    => CommentResource::collection($this->whenLoaded('replies')),

            // preparado para likes — retorna 0 até implementar
            'likes_count' => $this->whenNotNull($this->likes_count, 0),
        ];
    }
}
