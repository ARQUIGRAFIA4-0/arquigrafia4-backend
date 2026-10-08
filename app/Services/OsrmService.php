<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OsrmService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.osrm.url');
    }

    public function route(array $coordinates): array
    {
        $path = collect($coordinates)
            ->map(fn (array $coordinate) => "{$coordinate[0]},{$coordinate[1]}")
            ->implode(';');

        $response = Http::get(
            "{$this->baseUrl}/route/v1/walking/{$path}",
            [
                'overview' => 'full',
                'geometries' => 'geojson',
            ]
        );

        $response->throw();

        return $response->json();
    }
}