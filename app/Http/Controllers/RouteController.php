<?php

namespace App\Http\Controllers;

use App\Services\OsrmService;
use App\Support\Haversine;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function route(Request $request, OsrmService $osrm)
    {
        $maxStops = config('services.percursos.max_stops');

        $validated = $request->validate([
            'coordinates' => ['required', 'array', 'min:2', "max:{$maxStops}"],
            'coordinates.*' => ['required', 'array', 'size:2'],
            'coordinates.*.0' => ['required', 'numeric', 'between:-180,180'],
            'coordinates.*.1' => ['required', 'numeric', 'between:-90,90'],
        ], [
            'coordinates.max' => "O percurso pode ter no máximo {$maxStops} paradas.",
        ]);

        $maxMeters = config('services.percursos.max_distance_km') * 1000;

        // A rota a pé nunca é mais curta que a linha reta entre as paradas. Se a linha reta já
        // passa do limite, recusa sem chamar o OSRM: uma rota longa varre boa parte do grafo e
        // traz do disco páginas que o cache (limitado pelo mem_limit) precisaria para as demais.
        $straightLine = Haversine::pathMeters($validated['coordinates']);

        if ($straightLine > $maxMeters) {
            return $this->tooLong($straightLine, $maxMeters, 'Em linha reta, as paradas já somam %s km. O máximo permitido é %s km.');
        }

        $result = $osrm->route(
            $validated['coordinates']
        );

        // Ponto fora do Brasil ou sem via a pé por perto. A mensagem do OSRM traz o índice:
        // "Could not find a matching segment for coordinate 3".
        if (($result['code'] ?? null) === 'NoSegment') {
            preg_match('/coordinate (\d+)/', $result['message'] ?? '', $match);

            return response()->json([
                'code' => 'OutOfArea',
                'message' => 'Este ponto está fora da área atendida (Brasil) ou longe de qualquer via para pedestres.',
                'coordinateIndex' => isset($match[1]) ? (int) $match[1] : null,
            ], 422);
        }

        $distance = $result['routes'][0]['distance'] ?? 0;

        if ($distance > $maxMeters) {
            return $this->tooLong($distance, $maxMeters, 'O percurso ficaria com %s km. O máximo permitido é %s km.');
        }

        return response()->json($result);
    }

    private function tooLong(float $distance, float $maxMeters, string $message)
    {
        return response()->json([
            'code' => 'TooLong',
            'message' => sprintf(
                $message,
                number_format($distance / 1000, 1, ',', '.'),
                number_format($maxMeters / 1000, 0, ',', '.')
            ),
            'distanceMeters' => $distance,
            'maxDistanceMeters' => $maxMeters,
        ], 422);
    }
}
