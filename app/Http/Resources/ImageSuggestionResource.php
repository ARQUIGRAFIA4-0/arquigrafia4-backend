<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageSuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'image_id'    => $this->image_id,
            'user_id'     => $this->user_id,
            'status'      => $this->status,
            'payload'     => $this->payload,
            'review_note' => $this->review_note,
            'reviewed_at' => $this->reviewed_at,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,

            'image'    => $this->whenLoaded('image'),
            'user'     => $this->whenLoaded('user'),
            'reviewer' => $this->whenLoaded('reviewer'),
        ];
    }
}
