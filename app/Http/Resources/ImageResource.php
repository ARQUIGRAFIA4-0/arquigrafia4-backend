<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        if ($this->sizes) {
            $data['thumb_url'] = "iiif/{$this->id}/full/{$this->sizes['thumb']['width']},{$this->sizes['thumb']['height']}/0/default.jpg";
            $data['mid_url'] = "iiif/{$this->id}/full/{$this->sizes['mid']['width']},{$this->sizes['mid']['height']}/0/default.jpg";
            $data['full_url'] = "iiif/{$this->id}/full/max/0/default.jpg";
        }

        return $data;
    }
}
