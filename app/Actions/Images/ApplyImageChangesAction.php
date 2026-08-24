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

            // Works
            if (array_key_exists('works', $data)) {
                $image->works()->sync($data['works'] ?? []);
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
                $current = $image->locations()->first();

                // Locations podem ser compartilhadas entre imagens (herança da
                // migração legada, que agrupava por coordenada). Editar a linha
                // no lugar alteraria a localização de todas as outras imagens,
                // então clonamos antes de gravar (copy-on-write).
                $isShared = $current
                    && $current->images()->where('vrac_images.id', '!=', $image->id)->exists();

                $location = ($current && ! $isShared) ? $current : new Location();

                if ($isShared) {
                    $location->latitude = $current->latitude;
                    $location->longitude = $current->longitude;
                    $location->label = $current->label;
                }

                // Só sobrescreve os campos enviados: atualizar apenas o rótulo
                // não pode apagar as coordenadas já existentes.
                foreach (['latitude', 'longitude', 'location_label'] as $field) {
                    if (array_key_exists($field, $data)) {
                        $column = $field === 'location_label' ? 'label' : $field;
                        $location->$column = $data[$field];
                    }
                }

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
                $date->circa_earliest_date = $data['circa'] ?? 0;
                $date->latest_date = $data['latest_date'] ?? null;
                $date->circa_latest_date = $data['circa'] ?? 0;
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
                'works.titles',
                'works.location',
            ]);
        });
    }
}