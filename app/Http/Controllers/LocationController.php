<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\VRACore\VRACImage;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
use Illuminate\Support\Facades\Cache;

class LocationController extends Controller
{
    public function geojson()
    {
        $geojson = Cache::remember('locations.geojson', 3600, function () {
            $images = VRACImage::whereHas('locations')
                ->with(['locations', 'titles'])
                ->get();

            $features = [];

            foreach ($images as $image) {
                $title = $image->titles->first()?->label;
                $thumbUrl = $image->path('thumb', 'url');

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
