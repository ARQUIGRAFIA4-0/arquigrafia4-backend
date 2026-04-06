<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\VRACore\VRACImage;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
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
     * Retorna todas as localizações de imagens em formato GeoJSON. A resposta pode ser grande.
     *
     * @unauthenticated
     *
     * @responseField type string Always "FeatureCollection".
     * @responseField features object[] Array of GeoJSON Feature objects.
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
     *         "image_id": "9d5a3e3c-7b2f-4a1e-8c9d-1f2e3a4b5c6d",
     *         "title": "Edifício Copan",
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
                        $sizes = $image->sizes;
                        $thumbUrl = ($sizes['thumb']['width'] ?? null) !== null
                            ? $image->path('thumb', 'url')
                            : null;

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
                                    'image_id' => $image->id,
                                    'title' => $title,
                                    'thumb_url' => $thumbUrl,
                                ],
                            ];
                        }
                    }
                });

            return [
                'type' => 'FeatureCollection',
                'features' => $features,
            ];
        });

        return response()->json($geojson);
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
