<?php

namespace App\Actions\Works;

use App\Models\VRACore\VRACWork;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApplyWorkChangesAction
{
    /**
     * Map of payload keys (snake_case, as used by the work API contract)
     * to the corresponding VRACWork relation method.
     */
    private const RELATION_MAP = [
        'titles' => 'titles',
        'agents' => 'agents',
        'dates' => 'dates',
        'materials' => 'materials',
        'techniques' => 'techniques',
        'style_periods' => 'stylePeriods',
        'cultural_contexts' => 'culturalContexts',
        'work_types' => 'workTypes',
        'subjects' => 'subjects',
    ];

    public function execute(VRACWork $work, array $data): VRACWork
    {
        return DB::transaction(function () use ($work, $data) {

            if (array_key_exists('location_id', $data)) {
                $work->location_id = $data['location_id'];
                $work->save();
            }

            foreach (self::RELATION_MAP as $key => $relation) {
                if (array_key_exists($key, $data)) {
                    $work->{$relation}()->sync($data[$key] ?? []);
                }
            }

            // A work's location may feed the geojson layer via its images.
            Cache::forget('locations.geojson');

            return $work->fresh()->load(VRACWork::RELATIONS);
        });
    }
}
