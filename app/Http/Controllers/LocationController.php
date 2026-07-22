<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\SearchLocationRequest;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
use App\Models\Location;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACWork;
use App\Services\ImageSearchService;
use Illuminate\Database\Eloquent\Builder;
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

            $this->appendImageFeatures(VRACImage::whereHas('locations'), $features);
            $this->appendWorkFeatures(VRACWork::whereHas('location'), $features);

            return [
                'type' => 'FeatureCollection',
                'features' => $features,
            ];
        });

        return response()->json($geojson);
    }

    /**
     * GeoJSON filtrado
     *
     * Retorna localizações em formato GeoJSON aplicando os mesmos filtros da busca de imagens
     * (grid/mosaico), para manter o mapa em sincronia com as demais visualizações. Aceita todos
     * os filtros de `GET /api/images` (exceto ordenação/paginação, irrelevantes para o mapa).
     *
     * A camada de obras acompanha os filtros de imagem: uma obra só aparece quando possui ao menos
     * uma imagem que satisfaz os filtros. Use `works_only=true` para exibir apenas marcadores de obras.
     *
     * @unauthenticated
     *
     * @responseField type string Always "FeatureCollection".
     * @responseField features object[] Array of GeoJSON Feature objects.
     */
    public function filteredGeojson(SearchLocationRequest $request, ImageSearchService $searchService)
    {
        $validated = $request->validated();
        $worksOnly = (bool) ($validated['works_only'] ?? false);

        // Only real search filters drive ImageSearchService; map-specific/query concerns are stripped.
        $filters = collect($validated)
            ->except(['works_only', 'sort_by', 'sort_order'])
            ->all();

        $cacheKey = 'locations.geojson.filtered.'.md5(json_encode([
            'filters' => $filters,
            'works_only' => $worksOnly,
        ]));

        $geojson = Cache::remember($cacheKey, 3600, function () use ($searchService, $filters, $worksOnly) {
            $features = [];

            if (! $worksOnly) {
                $imageQuery = VRACImage::query()->whereHas('locations');
                $searchService->apply($imageQuery, $filters, sortable: false);
                $this->appendImageFeatures($imageQuery, $features);
            }

            // A work stays on the map only when one of its images matches the same filters.
            $workQuery = VRACWork::query()
                ->whereHas('location')
                ->whereHas('images', function (Builder $q) use ($searchService, $filters) {
                    $searchService->apply($q, $filters, sortable: false);
                });
            $this->appendWorkFeatures($workQuery, $features);

            return [
                'type' => 'FeatureCollection',
                'features' => $features,
            ];
        });

        return response()->json($geojson);
    }

    /**
     * Append image point features (one per located image) to the accumulator.
     *
     * @param  Builder  $query  A VRACImage query already scoped to images with locations.
     * @param  array<int, array<string, mixed>>  $features
     */
    private function appendImageFeatures(Builder $query, array &$features): void
    {
        $query
            ->with(['locations', 'titles'])
            ->reorder()
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
    }

    /**
     * Append work point features (one per located work) to the accumulator.
     *
     * @param  Builder  $query  A VRACWork query already scoped to works with a location.
     * @param  array<int, array<string, mixed>>  $features
     */
    private function appendWorkFeatures(Builder $query, array &$features): void
    {
        $query
            ->with(['location', 'titles', 'images'])
            ->reorder()
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
