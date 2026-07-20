<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkSuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_id' => $this->work_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'payload' => $this->payload,
            'review_note' => $this->review_note,
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'work' => $this->whenLoaded('work'),
            'user' => $this->whenLoaded('user'),
            'reviewer' => $this->whenLoaded('reviewer'),
        ];
    }
}
