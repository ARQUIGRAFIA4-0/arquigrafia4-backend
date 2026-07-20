<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\StoreLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
use App\Models\Location;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACWork;
use Illuminate\Support\Facades\Cache;

/**
 * @group Localizações
 *
 * Dados geográficos e GeoJSON das imagens.
 */
class LocationController extends Controller
{
    /**
     * GeoJSON
     *
     * Retorna todas as localizações de imagens e obras em formato GeoJSON. A resposta pode ser grande.
     * Cada feature carrega `feature_type` (`image` ou `work`) para que o mapa escolha o marcador adequado.
     *
     * @unauthenticated
     *
     * @responseField type string Always "FeatureCollection".
     * @responseField features object[] Array of GeoJSON Feature objects.
     *
     * @response scenario="Exemplo (truncado)" {
     *   "type": "FeatureCollection",
     *   "features": [
     *     {
     *       "type": "Feature",
     *       "geometry": {
     *         "type": "Point",
     *         "coordinates": [-46.7318, -23.5258]
     *       },
     *       "properties": {
     *         "feature_type": "image",
     *         "image_id": "9d5a3e3c-7b2f-4a1e-8c9d-1f2e3a4b5c6d",
     *         "title": "Edifício Copan",
     *         "thumb_url": "iiif/9d5a3e3c-7b2f-4a1e-8c9d-1f2e3a4b5c6d/full/200,200/0/default.jpg"
     *       }
     *     },
     *     {
     *       "type": "Feature",
     *       "geometry": {
     *         "type": "Point",
     *         "coordinates": [-46.6333, -23.5505]
     *       },
     *       "properties": {
     *         "feature_type": "work",
     *         "work_id": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
     *         "title": "Catedral da Sé",
     *         "thumb_url": "iiif/9d5a3e3c-7b2f-4a1e-8c9d-1f2e3a4b5c6d/full/200,200/0/default.jpg"
     *       }
     *     }
     *   ]
     * }
     */
    public function geojson()
    {
        $geojson = Cache::remember('locations.geojson', 3600, function () {
            $features = [];

            VRACImage::whereHas('locations')
                ->with(['locations', 'titles'])
                ->chunkById(200, function ($images) use (&$features) {
                    foreach ($images as $image) {
                        $title = $image->titles->first()?->label;
                        $thumbUrl = $this->thumbUrl($image);

                        foreach ($image->locations as $loc) {
                            if ($loc->latitude === null || $loc->longitude === null) {
                                continue;
                            }

                            $features[] = [
                                'type' => 'Feature',
                                'geometry' => [
                                    'type' => 'Point',
                                    'coordinates' => [(float) $loc->longitude, (float) $loc->latitude],
                                ],
                                'properties' => [
                                    'feature_type' => 'image',
                                    'image_id' => $image->id,
                                    'title' => $title,
                                    'thumb_url' => $thumbUrl,
                                ],
                            ];
                        }
                    }
                });

            VRACWork::whereHas('location')
                ->with(['location', 'titles', 'images'])
                ->chunkById(200, function ($works) use (&$features) {
                    foreach ($works as $work) {
                        $loc = $work->location;
                        if ($loc === null || $loc->latitude === null || $loc->longitude === null) {
                            continue;
                        }

                        $title = $work->titles->first()?->label;
                        $thumbUrl = $this->thumbUrl($work->images->first());

                        $features[] = [
                            'type' => 'Feature',
                            'geometry' => [
                                'type' => 'Point',
                                'coordinates' => [(float) $loc->longitude, (float) $loc->latitude],
                            ],
                            'properties' => [
                                'feature_type' => 'work',
                                'work_id' => $work->id,
                                'title' => $title,
                                'thumb_url' => $thumbUrl,
                            ],
                        ];
                    }
                });

            return [
                'type' => 'FeatureCollection',
                'features' => $features,
            ];
        });

        return response()->json($geojson);
    }

    /**
     * Resolve the IIIF thumbnail URL for an image, or null when unavailable.
     */
    private function thumbUrl(?VRACImage $image): ?string
    {
        if ($image === null) {
            return null;
        }

        $sizes = $image->sizes;

        return ($sizes['thumb']['width'] ?? null) !== null
            ? $image->path('thumb', 'url')
            : null;
    }

    public function index()
    {
        //
    }

    public function store(StoreLocationRequest $request)
    {
        //
    }

    public function show(Location $location)
    {
        //
    }

    public function update(UpdateLocationRequest $request, Location $location)
    {
        //
    }

    public function destroy(Location $location)
    {
        //
    }
}
