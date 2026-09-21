<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PercursoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'title' => $this->title,
            'stops' => $this->stops->map(fn ($stop) => [
                'type'        => $stop->type,
                'title'       => $stop->title,
                'order'       => $stop->order,
                'coordinates' => [$stop->longitude, $stop->latitude],
                'imageId'     => $stop->image_id,
            ])->values(),
            'route' => [
                'isStreet'        => $this->is_street,
                'distanceMeters'  => $this->distance_meters,
                'durationSeconds' => $this->duration_seconds,
                'coordinates'     => $this->route_coordinates ?? [],
            ],
        ];
    }
}
