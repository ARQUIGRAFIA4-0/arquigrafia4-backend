<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectiveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'foundation_date' => $this->foundation_date,
            'location' => $this->location,
            'avatar_path' => $this->avatar_path,
            'description' => $this->description,
            'socials' => $this->socials,
            'subjects' => $this->whenLoaded('subjects', fn () => $this->subjects->pluck('id')),
            'members_count' => $this->whenCounted('members'),
            'members' => $this->whenLoaded('members', function () {
                return $this->members->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_path' => $user->avatar_path,
                    'role' => $user->pivot->role,
                ]);
            }),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
