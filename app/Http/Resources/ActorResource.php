<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ActorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type'       => $this->type,
            'id'         => $this->id,
            'name'       => $this->name,
            'avatar_url' => $this->avatar_path ? Storage::url($this->avatar_path) : null,
            'legacy_id'  => $this->legacy_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
