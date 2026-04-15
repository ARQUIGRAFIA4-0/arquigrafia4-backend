<?php

namespace App\Actions\Images;

use App\Models\Location;
use App\Models\VRACore\VRACAgent;
use App\Models\VRACore\VRACAgentRole;
use App\Models\VRACore\VRACDate;
use App\Models\VRACore\VRACDescription;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACTitle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApplyImageChangesAction
{
    public function execute(VRACImage $image, array $data): VRACImage
    {
        return DB::transaction(function () use ($image, $data) {

            // Campos base
            if (array_key_exists('user_id', $data)) {
                $image->user_id = $data['user_id'];
            }

            if (array_key_exists('collective_id', $data)) {
                $image->collective_id = $data['collective_id'];
            }

            $image->save();

            // Title
            if (array_key_exists('title', $data) && filled($data['title'])) {
                $title = $image->titles()->first() ?? new VRACTitle();

                $title->label = $data['title'];
                $title->type = 'other';
                $title->save();

                $image->titles()->sync([$title->id]);
            }

            // Photographer
            if (array_key_exists('photographer', $data) && filled($data['photographer'])) {
                $photographerRole = VRACAgentRole::getPhotographer();

                if (! $photographerRole) {
                    abort(response()->json([
                        'message' => 'No existe el rol photographer',
                    ], 422));
                }

                $agentPhotographer = VRACAgent::firstOrCreate([
                    'role_id' => $photographerRole->id,
                    'contributor_name_id' => $data['photographer'],
                ]);

                $image->agents()->sync([$agentPhotographer->id]);
            }

            // Subjects
            if (array_key_exists('subjects', $data)) {
                $image->subjects()->sync($data['subjects'] ?? []);
            }

            // Description
            if (array_key_exists('description', $data) && filled($data['description'])) {
                $description = $image->descriptions()->first() ?? new VRACDescription();

                $description->text = $data['description'];
                $description->save();

                $image->descriptions()->sync([$description->id]);
            }

            // Location
            if (
                array_key_exists('latitude', $data) ||
                array_key_exists('longitude', $data) ||
                array_key_exists('location_label', $data)
            ) {
                $location = $image->locations()->first() ?? new Location();

                $location->latitude = $data['latitude'] ?? null;
                $location->longitude = $data['longitude'] ?? null;
                $location->label = $data['location_label'] ?? null;
                $location->save();

                $image->locations()->sync([$location->id]);
            }

            // Dates
            if (
                array_key_exists('earliest_date', $data) ||
                array_key_exists('latest_date', $data) ||
                array_key_exists('circa', $data)
            ) {
                $date = $image->dates()->first() ?? new VRACDate();

                $date->type = 'creation';
                $date->earliest_date = $data['earliest_date'] ?? null;
                $date->circa_earliest_date = $data['circa'] ?? null;
                $date->latest_date = $data['latest_date'] ?? null;
                $date->circa_latest_date = $data['circa'] ?? null;
                $date->save();

                $image->dates()->sync([$date->id]);
            }

            Cache::forget('locations.geojson');

            // Recargar con relaciones
            return $image->fresh()->load([
                'agents.contributorName',
                'user',
                'collective',
                'culturalContexts',
                'dates',
                'descriptions',
                'titles',
                'techniques',
                'workTypes',
                'materials',
                'stylePeriods',
                'measurements',
                'stateEditions',
                'sources',
                'rights',
                'inscriptions',
                'subjects',
                'locations',
            ]);
        });
    }
}