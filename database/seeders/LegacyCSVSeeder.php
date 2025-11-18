<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACTitle;
use App\Models\VRACore\VRACDescription;
use App\Models\VRACore\VRACDate;
use App\Models\VRACore\VRACSubject;
use App\Models\VRACore\VRACRight;
use App\Models\VRACore\VRACAgent;
use App\Models\VRACore\VRACAgentRole;
use App\Models\VRACore\VRACContributorName;

class LegacyCSVSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default CSV path - change or place your CSV here
        $csvPath = database_path('seeders/legacy.csv');
        if (! file_exists($csvPath)) {
            $this->command->error("CSV file not found: {$csvPath}");
            return;
        }

        // Preload subjects into a normalized map for fast lookup
        $subjectIndex = [];
        VRACSubject::chunk(500, function ($rows) use (&$subjectIndex) {
            foreach ($rows as $r) {
                $subjectIndex[$this->normalize($r->term)] = $r;
            }
        });

        // Ensure Photographer role exists
        $photographerRole = VRACAgentRole::firstOrCreate(
            ['label' => 'Photographer'],
            ['id' => (string) Str::uuid()]
        );

        $handle = fopen($csvPath, 'r');
        $header = null;
        $rowCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (! $header) {
                $header = $row;
                continue;
            }

            $data = array_combine($header, $row);
            if (! $data) {
                continue;
            }

            DB::transaction(function () use ($data, &$subjectIndex, $photographerRole) {
                // Image
                $imageId = trim($data['VRA_UUID']);
                if (! $imageId) {
                    return;
                }

                $image = VRACImage::firstOrCreate(
                    ['id' => $imageId],
                    ['ref_id' => null, 'source' => 'legacy']
                );

                // Title
                $titleText = trim($data['VRA_Title'] ?? '');
                if ($titleText !== '') {
                    $title = VRACTitle::firstOrCreate(
                        ['label' => $titleText],
                        ['id' => (string) Str::uuid(), 'type' => 'other']
                    );
                    $image->titles()->syncWithoutDetaching($title->id);
                }

                // Description
                $descText = trim($data['VRA_Description'] ?? '');
                if ($descText !== '') {
                    $description = VRACDescription::firstOrCreate(
                        ['text' => $descText],
                        ['id' => (string) Str::uuid()]
                    );
                    $image->descriptions()->syncWithoutDetaching($description->id);
                }

                // Dates
                $earliest = trim($data['VRA_EarliestDate'] ?? '');
                $latest = trim($data['VRA_LatestDate'] ?? '');
                $circa = trim($data['VRA_DateCirca'] ?? '');
                $circaBool = in_array(strtolower($circa), ['1', 'true', 'yes', 'y'], true) ? 1 : 0;
                if ($earliest !== '' || $latest !== '') {
                    $date = VRACDate::firstOrCreate(
                        [
                            'type' => 'creation',
                            'earliest_date' => $earliest,
                            'latest_date' => $latest,
                        ],
                        [
                            'id' => (string) Str::uuid(),
                            'circa_earliest_date' => $circaBool,
                            'circa_latest_date' => $circaBool,
                        ]
                    );
                    $image->dates()->syncWithoutDetaching($date->id);
                }

                // Subjects (comma separated)
                $subjectsRaw = trim($data['VRA_Subjects'] ?? '');
                if ($subjectsRaw !== '') {
                    $terms = array_filter(array_map('trim', explode(',', $subjectsRaw)));
                    foreach ($terms as $term) {
                        $norm = $this->normalize($term);
                        if (isset($subjectIndex[$norm])) {
                            $sub = $subjectIndex[$norm];
                        } else {
                            $sub = VRACSubject::create([
                                'id' => (string) Str::uuid(),
                                'term' => $term,
                                'vocab' => 'Arquigrafia',
                            ]);
                            $subjectIndex[$norm] = $sub;
                        }
                        $image->subjects()->syncWithoutDetaching($sub->id);
                    }
                }

                // Rights (VRA_Right contains existing VRACRight id)
                $rightId = trim($data['VRA_Right'] ?? '');
                if ($rightId !== '') {
                    // ensure it exists - if not, skip
                    $right = VRACRight::find($rightId);
                    if ($right) {
                        $image->rights()->syncWithoutDetaching($right->id);
                    }
                }

                // Agent - VRA_Agent contains user id
                $agentUserId = trim($data['VRA_Agent'] ?? '');
                if ($agentUserId !== '') {
                    $user = User::find($agentUserId);
                    if ($user) {
                        // contributor name from user
                        $contrib = VRACContributorName::firstOrCreate(
                            ['name' => $user->name],
                            ['id' => (string) Str::uuid(), 'type' => 'personal']
                        );

                        $agent = VRACAgent::firstOrCreate(
                            [
                                'contributor_name_id' => $contrib->id,
                                'role_id' => $photographerRole->id,
                            ],
                            ['id' => (string) Str::uuid()]
                        );

                        $image->agents()->syncWithoutDetaching($agent->id);
                    }
                }
            });

            $rowCount++;
        }

        fclose($handle);

        $this->command->info("Imported/processed {$rowCount} rows from CSV.");
    }

    private function normalize(string $value): string
    {
        // remove accents, hyphens and whitespace, lowercase
        $norm = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $norm = preg_replace('/[^A-Za-z0-9]/', '', $norm);
        return strtolower($norm);
    }
}
