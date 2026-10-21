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
            'stops' => $this->stops->map(function ($stop) {
                // Ponto de imagem cuja imagem foi apagada sai como ponto sem imagem relacionada.
                $hasImage = $stop->type === 'image' && $stop->image !== null;

                return [
                    'type'        => $hasImage ? 'image' : 'custom',
                    'title'       => $stop->title,
                    'order'       => $stop->order,
                    'coordinates' => [$stop->longitude, $stop->latitude],
                    'imageId'     => $hasImage ? $stop->image_id : null,
                ];
            })->values(),
            'route' => [
                'isStreet'        => $this->is_street,
                'distanceMeters'  => $this->distance_meters,
                'durationSeconds' => $this->duration_seconds,
                'coordinates'     => $this->route_coordinates ?? [],
            ],
        ];
    }
}
