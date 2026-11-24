<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OsrmService
{
    private string $baseUrl;

    private int $snapRadius;

    public function __construct()
    {
        $this->baseUrl = config('services.osrm.url');
        $this->snapRadius = config('services.osrm.snap_radius_meters');
    }

    /**
     * Rota a pé entre as coordenadas.
     *
     * Sem `radiuses` o OSRM encaixa qualquer ponto na via mais próxima do grafo, mesmo a milhares
     * de km (um ponto em Lisboa vira um ponto no litoral brasileiro). Com o raio, pontos fora do
     * Brasil ou longe de qualquer via retornam `code: NoSegment` (HTTP 400).
     */
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
                'radiuses' => implode(';', array_fill(0, count($coordinates), $this->snapRadius)),
            ]
        );

        // NoSegment / TooBig etc. vêm como 400 com corpo JSON; só falhas do serviço viram exceção.
        if ($response->serverError() || !is_array($response->json())) {
            $response->throw();
        }

        return $response->json();
    }
}
