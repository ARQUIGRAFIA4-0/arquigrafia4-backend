<?php

namespace Database\Seeders;

use App\Models\VRACore\VRACAgent;
use App\Models\VRACore\VRACAgentRole;
use App\Models\VRACore\VRACContributorName;
use App\Models\VRACore\VRACDate;
use App\Models\VRACore\VRACDescription;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACSubject;
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
                [
                    'id' => Str::uuid(),
                    'type' => 'corporate'
                ],

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
                    'circa_earliest_date' => 1,
                    'latest_date' => '1980-01-01T00:00:00',
                    'circa_latest_date' => 1,
                ],
                [
                    'id' => Str::uuid(),
                ]
            );

            $subjects = collect([
                'Estação Rodoviária',
                'Concreto Aparente',
                'Pilar',
                'Abertura Zenital',
                'Cobertura',
                'Canteiro de Obras'
            ])->map(fn($term) => VRACSubject::firstOrCreate([
                'id' => Str::uuid(),
                'term' => $term,
            ]));

            $subjectIds = $subjects->pluck('id')->toArray();

            $image->title()->attach($title->id);
            $image->descriptions()->attach($description->id);
            $image->agents()->attach($agent->id);
            $image->dates()->attach($date->id);
            $image->subjects()->attach($subjectIds);
        });
    }
}
