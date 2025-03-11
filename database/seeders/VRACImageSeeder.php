<?php

namespace Database\Seeders;

use App\Models\VRACore\VRACAgent;
use App\Models\VRACore\VRACAgentRole;
use App\Models\VRACore\VRACContributorName;
use App\Models\VRACore\VRACDate;
use App\Models\VRACore\VRACDescription;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACTitle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VRACImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $image = VRACImage::create([
                'id' => Str::uuid(),
            ]);

            $title = VRACTitle::firstOrCreate(
                ['label' => 'Estação Rodoviária de Jaú'],
                ['id' => Str::uuid()]
            );

            $description = VRACDescription::firstOrCreate(
                ['text' => 'Vista geral da estação rodoviária de Jaú em fase de construção.'],
                ['id' => Str::uuid()]
            );

            $contributor = VRACContributorName::firstOrCreate(
                ['name' => 'Biblioteca da FAUUSP'],
                ['id' => Str::uuid()]
            );

            $role = VRACAgentRole::firstOrCreate(
                ['label' => 'Criação'],
                ['id' => Str::uuid()]
            );

            $agent = VRACAgent::firstOrCreate(
                [
                    'contributor_name_id' => $contributor->id,
                    'role_id' => $role->id
                ],
                ['id' => Str::uuid()]
            );

            $date = VRACDate::firstOrCreate(
                [
                    'type' => 'creation',
                    'earliest_date' => '1971-01-01T00:00:00',
                    'earliest_date_circa' => 1,
                    'latest_date' => '1980-01-01T00:00:00',
                    'latest_date_circa' => 1,
                ],
                [
                    'id' => Str::uuid(),
                ]
            );

            $image->title()->attach($title->id);
            $image->description()->attach($description->id);
            $image->agent()->attach($agent->id);
            $image->date()->attach($date->id);
        });
    }
}
