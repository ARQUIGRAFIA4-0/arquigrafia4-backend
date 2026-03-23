<?php

namespace Database\Seeders;

use App\Models\Collective;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CollectiveSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/legacy-collectives.csv');
        if (! file_exists($csvPath)) {
            $this->command->error("CSV file not found: {$csvPath}");
            return;
        }

        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle);

        $collectivesCreated = 0;
        $membersAttached = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            DB::transaction(function () use ($data, &$collectivesCreated, &$membersAttached) {
                $collective = Collective::firstOrCreate(
                    ['id' => $data['collective_uuid']],
                    [
                        'name' => $data['collective_name'],
                        'email' => $data['collective_email'],
                        'legacy_id' => (int) $data['collective_legacy_id'],
                        'created_at' => $data['collective_created_at'],
                        'updated_at' => $data['collective_created_at'],
                    ]
                );

                if ($collective->wasRecentlyCreated) {
                    $collectivesCreated++;
                }

                // Attach member if not already attached
                if (! $collective->members()->where('user_id', $data['user_uuid'])->exists()) {
                    $collective->members()->attach($data['user_uuid'], [
                        'role' => $data['role'],
                    ]);
                    $membersAttached++;
                }
            });
        }

        fclose($handle);

        $this->command->info("Created {$collectivesCreated} collectives and attached {$membersAttached} members.");
    }
}
