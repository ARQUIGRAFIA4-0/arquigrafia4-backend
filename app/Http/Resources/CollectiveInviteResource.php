<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectiveInviteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'collective_id' => $this->collective_id,
            'token' => $this->token,
            'is_single_use' => $this->is_single_use,
            'uses' => $this->uses,
            'max_uses' => $this->max_uses,
            'revoked_at' => $this->revoked_at,
            'expires_at' => $this->expires_at,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
