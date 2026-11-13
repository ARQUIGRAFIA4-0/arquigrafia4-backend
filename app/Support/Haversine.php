<?php

namespace App\Support;

/**
 * Distância em linha reta sobre a esfera terrestre, com pontos no formato [lon, lat] (GeoJSON/OSRM).
 */
class Haversine
{
    private const EARTH_RADIUS_METERS = 6371008.8;

    public static function meters(array $a, array $b): float
    {
        $lat1 = deg2rad($a[1]);
        $lat2 = deg2rad($b[1]);
        $h = sin(($lat2 - $lat1) / 2) ** 2
            + cos($lat1) * cos($lat2) * sin(deg2rad($b[0] - $a[0]) / 2) ** 2;

        return 2 * self::EARTH_RADIUS_METERS * asin(min(1, sqrt($h)));
    }

    /**
     * Soma das distâncias entre pontos consecutivos. Nenhum caminho real entre eles é mais curto.
     */
    public static function pathMeters(array $points): float
    {
        $points = array_values($points);
        $total = 0.0;

        for ($i = 1; $i < count($points); $i++) {
            $total += self::meters($points[$i - 1], $points[$i]);
        }

        return $total;
    }
}
