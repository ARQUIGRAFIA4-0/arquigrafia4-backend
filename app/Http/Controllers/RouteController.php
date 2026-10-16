<?php

namespace App\Http\Controllers;

use App\Services\OsrmService;
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

        $maxKm = config('services.percursos.max_distance_km');
        $distance = $result['routes'][0]['distance'] ?? 0;

        if ($distance > $maxKm * 1000) {
            return response()->json([
                'code' => 'TooLong',
                'message' => sprintf(
                    'O percurso ficaria com %s km. O máximo permitido é %s km.',
                    number_format($distance / 1000, 1, ',', '.'),
                    number_format($maxKm, 0, ',', '.')
                ),
                'distanceMeters' => $distance,
                'maxDistanceMeters' => $maxKm * 1000,
            ], 422);
        }

        return response()->json($result);
    }
}
