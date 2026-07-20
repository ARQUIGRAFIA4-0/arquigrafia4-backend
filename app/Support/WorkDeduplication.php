<?php

namespace App\Support;

use App\Models\VRACore\VRACTitle;
use App\Models\VRACore\VRACWork;

/**
 * Detects duplicate works.
 *
 * Two works are considered duplicates when they share the same primary
 * (pref = true) title label AND their locations fall within
 * {@see self::RADIUS_METERS} of each other. Different works legitimately
 * share a name (e.g. "Igreja de São Francisco") in different places, so the
 * name alone is never enough — the location proximity is what distinguishes a
 * genuine duplicate from a namesake.
 */
class WorkDeduplication
{
    /**
     * Works whose primary title matches and whose location is within this many
     * meters are treated as the same work.
     */
    public const RADIUS_METERS = 100;

    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * Find an existing work that would duplicate a work carrying $titleIds at
     * ($latitude, $longitude), or null when none exists.
     *
     * @param  array<int, string>  $titleIds  UUIDs of vrac_titles attached to the candidate work.
     * @param  string|null  $ignoreWorkId  Work to exclude from the search (the one being updated).
     */
    public static function findDuplicate(
        array $titleIds,
        ?float $latitude,
        ?float $longitude,
        ?string $ignoreWorkId = null
    ): ?VRACWork {
        if (empty($titleIds) || $latitude === null || $longitude === null) {
            return null;
        }

        $primaryLabel = VRACTitle::whereIn('id', $titleIds)
            ->where('pref', true)
            ->value('label');

        if ($primaryLabel === null) {
            return null;
        }

        // Haversine distance in meters between the candidate coordinates and
        // each work's location. Kept as a raw expression so the radius filter
        // runs in the database rather than pulling every same-named work.
        $distance = sprintf(
            '(%d * acos(least(1, '
            .'cos(radians(?)) * cos(radians(locations.latitude)) * '
            .'cos(radians(locations.longitude) - radians(?)) + '
            .'sin(radians(?)) * sin(radians(locations.latitude))'
            .')))',
            self::EARTH_RADIUS_METERS
        );

        $query = VRACWork::query()
            ->join('locations', 'vrac_works.location_id', '=', 'locations.id')
            ->join('work_title', 'work_title.work_id', '=', 'vrac_works.id')
            ->join('vrac_titles', function ($join) {
                $join->on('vrac_titles.id', '=', 'work_title.title_id')
                    ->where('vrac_titles.pref', true);
            })
            ->where('vrac_titles.label', $primaryLabel)
            ->whereNotNull('locations.latitude')
            ->whereNotNull('locations.longitude')
            ->whereRaw("{$distance} <= ?", [
                $latitude,
                $longitude,
                $latitude,
                self::RADIUS_METERS,
            ])
            ->select('vrac_works.*');

        if ($ignoreWorkId !== null) {
            $query->where('vrac_works.id', '!=', $ignoreWorkId);
        }

        return $query->first();
    }
}
