<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\VRACore\VRACImage;
use App\Models\Location;
use App\Models\VRACore\VRACTitle;
use App\Models\VRACore\VRACDescription;
use App\Models\VRACore\VRACDate;
use App\Models\VRACore\VRACSubject;
use App\Models\VRACore\VRACRight;
use App\Models\VRACore\VRACAgent;
use App\Models\VRACore\VRACAgentRole;
use App\Models\VRACore\VRACContributorName;
use App\Models\VRACore\VRACTechnique;
use App\Models\Vocabulary;

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
                $subjectIndex[$r->term] = $r; //$this->normalize($r->term)
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

        // Count total rows for progress bar
        $totalRows = count(file($csvPath)) - 1; // Subtract 1 for the header row

        // Initialize progress bar
        $progressBar = $this->command->getOutput()->createProgressBar($totalRows);
        $progressBar->start();

        while (($row = fgetcsv($handle)) !== false) {
            if (! $header) {
                $header = $row;
                $expectedColumnCount = count($header);
                continue;
            }
            if (count($row) !== $expectedColumnCount) {
                // Log the error and skip the bad row
                $this->command->warn("\nSkipping row {$rowCount}: Column count mismatch.");
                $this->command->warn("Expected {$expectedColumnCount} columns, got " . count($row) . ".");
                // Optionally, show the raw data that caused the issue
                // $this->command->warn("Row Data: ".json_encode($row));

                $progressBar->advance();
                $rowCount++;
                continue; // Skip processing this row
            }

            $data = array_combine($header, $row);
            if (! $data) {
                continue;
            }

            // Replace all "NULL" strings with actual null values
            array_walk($data, function (&$value) {
                $value = strtoupper($value) === 'NULL' ? null : $value;
            });

            DB::transaction(function () use ($data, &$subjectIndex, $photographerRole) {
                // Image
                $imageId = trim($data['VRA_UUID']);
                $userUuid = trim($data['user_uuid']);
                if (! $imageId || ! $userUuid) {
                    return;
                }

                $image = VRACImage::withTrashed()->firstOrNew(['id' => $imageId]);
                $image->fill([
                    'user_id' => $userUuid,
                    'created_at' => $this->parseTimestamp($data['created_at'] ?? now()),
                    'updated_at' => $this->parseTimestamp($data['updated_at'] ?? now()),
                    'deleted_at' => $this->parseTimestamp($data['deleted_at'] ?? null),
                    'legacy_id' => trim($data['id']),
                ]);
                $image->save();

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
                $subjectsRaw = trim($data['tags'] ?? '');
                if ($subjectsRaw !== '') {
                    $terms = array_filter(array_map('trim', explode(',', $subjectsRaw)));
                    foreach ($terms as $term) {
                        $norm = $term; //$this->normalize($term);
                        if (isset($subjectIndex[$norm])) {
                            $sub = VRACSubject::firstOrCreate(
                                [
                                    'term' => $term
                                ],
                                [
                                    'id' => (string) Str::uuid(),
                                    'vocab' => 'VCAA'
                                ]
                            );
                        } else {
                            $sub = VRACSubject::firstOrCreate([
                                'term' => $term,
                            ], [
                                'id' => (string) Str::uuid(),
                                'vocab' => 'Arquigrafia'
                            ]);
                            $subjectIndex[$norm] = $sub;
                        }
                        $image->subjects()->syncWithoutDetaching($sub->id);
                    }
                }

                // Rights (VRA_Right contains existing VRACRight id)
                $license = trim($data['VRA_Right'] ?? '');
                if ($license !== '') {
                    // ensure it exists - if not, skip
                    $right = VRACRight::firstOrCreate(
                        ['href' => VRACRight::getLicenseMap()[$license] ?? null],
                        [
                            'text' => '',
                            'id' => (string) Str::uuid(),
                            'type' => 'other',
                            'rights_holder' => '',
                        ]
                    );
                    $image->rights()->syncWithoutDetaching($right->id);
                }

                // Agent - VRA_Agent contains user id
                $imageContributor = trim($data['VRA_ImageContributor'] ?? '');
                if ($imageContributor !== '') {

                    $contrib = VRACContributorName::firstOrCreate(
                        ['name' => $imageContributor],
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

                // Location: look for latitude, longitude and complete address in the CSV
                $lat = trim($data['latitude'] ?? '');
                $lng = trim($data['longitude'] ?? '');
                $label = trim($data['complete_address'] ?? '');

                if ($lat !== null && $lng !== null) {
                    // upsert by coordinates; set label if provided
                    $location = Location::firstOrCreate(
                        [
                            'latitude' => (float) $lat,
                            'longitude' => (float) $lng,
                        ],
                        [
                            'label' => $label,
                            'id' => (string) Str::uuid()
                        ]
                    );

                    // attach to image via the Location model relation (uses image_location pivot)
                    $location->images()->syncWithoutDetaching($image->id);
                }

                // Technique: upsert and link "Imagem digital" with vocab "VCAA" and ref_id 4269
                $technique = VRACTechnique::firstOrCreate(
                    [
                        'label' => 'Imagem digital',
                        'vocab' => 'VCAA',
                        'ref_id' => '4269',
                    ],
                    [
                        'id' => (string) Str::uuid()
                    ]
                );

                $image->techniques()->syncWithoutDetaching($technique->id);
            });

            // Advance the progress bar after processing each row
            $progressBar->advance();
            $rowCount++;
        }

        fclose($handle);

        // Finish the progress bar
        $progressBar->finish();
        $this->command->info("\nImported/processed {$rowCount} rows from CSV.");
    }

    private function normalize(string $value): string
    {
        // remove accents, hyphens and whitespace, lowercase
        $norm = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $norm = preg_replace('/[^A-Za-z0-9]/', '', $norm);
        return strtolower($norm);
    }

    /**
     * Parse a timestamp string into a Carbon instance or return null.
     *
     * @param string|null $timestamp
     * @return \Carbon\Carbon|string|null
     */
    private function parseTimestamp(?string $timestamp)
    {
        if (empty($timestamp)) {
            return null;
        }

        try {
            // Parse the timestamp into a Carbon instance
            return \Carbon\Carbon::parse($timestamp);
        } catch (\Exception $e) {
            // Log the error and return null if parsing fails
            $this->command->warn("Invalid timestamp format: {$timestamp}");
            return null;
        }
    }
}
