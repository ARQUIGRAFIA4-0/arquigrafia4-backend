<?php

namespace App\Console\Commands;

use App\Models\VRACore\VRACImage;
use App\Support\IiifInfo;
use Illuminate\Console\Command;

class FixIiifInfo extends Command
{
    protected $signature = 'images:fix-iiif-info
        {--id= : Only fix a single image}
        {--stale-only : Only rewrite files whose id does not already match config(iiif.base_url)}';

    protected $description = 'Rewrite existing info.json files (correct id from config + inject sizes) without re-tiling. Run after changing IIIF_BASE_URL.';

    public function handle(): int
    {
        $base = rtrim(config('iiif.base_url'), '/');
        $this->info("Target id base: {$base}");

        // Tiles exist on disk independently of soft-delete state, so include
        // trashed images — their info.json still needs a correct id.
        $query = VRACImage::withTrashed()->whereNotNull('processed_at');
        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $patched = 0;
        $skipped = 0;
        $missing = 0;

        $query->select(['id', 'sizes'])->chunkById(500, function ($images) use (&$patched, &$skipped, &$missing, $base) {
            foreach ($images as $image) {
                $path = $image->path('info', 'absolute');
                if (! is_file($path)) {
                    $missing++;

                    continue;
                }

                if ($this->option('stale-only')) {
                    $info = json_decode((string) file_get_contents($path), true);
                    $currentId = $info['id'] ?? $info['@id'] ?? null;
                    // Up to date = correct id AND a sizes array already present.
                    if ($currentId === "{$base}/{$image->id}" && isset($info['sizes'])) {
                        $skipped++;

                        continue;
                    }
                }

                IiifInfo::patch($image) ? $patched++ : $missing++;
            }
        });

        $this->info("Done. patched={$patched} skipped={$skipped} missing_info_json={$missing}");

        return self::SUCCESS;
    }
}
