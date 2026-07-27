<?php

namespace App\Console\Commands;

use App\Jobs\TileImage;
use App\Models\VRACore\VRACImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TileStatus extends Command
{
    protected $signature = 'images:tile-status
        {--dispatch : Queue a TileImage job for every actionable un-tiled image}';

    protected $description = 'Report IIIF tiling health and optionally re-queue un-tiled images (safe to run on a schedule)';

    public function handle(): int
    {
        $total = VRACImage::count();
        $tiled = VRACImage::whereNotNull('processed_at')->count();

        // Images without the DB marker fall into two buckets: those whose
        // original is on disk (actionable — can be tiled) and those without an
        // original (orphans — legacy records whose file never migrated).
        $actionable = [];
        $orphans = 0;

        VRACImage::whereNull('processed_at')
            ->select(['id', 'sizes'])
            ->chunkById(500, function ($images) use (&$actionable, &$orphans) {
                foreach ($images as $image) {
                    if (file_exists($image->path('original', 'absolute'))) {
                        $actionable[] = $image->id;
                    } else {
                        $orphans++;
                    }
                }
            });

        $queued = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();

        $this->table(
            ['metric', 'count'],
            [
                ['active images', $total],
                ['tiled (processed_at set)', $tiled],
                ['un-tiled, actionable', count($actionable)],
                ['un-tiled, orphans (no original)', $orphans],
                ['jobs queued', $queued],
                ['jobs failed', $failed],
            ]
        );

        if ($this->option('dispatch') && count($actionable) > 0) {
            foreach ($actionable as $id) {
                if ($image = VRACImage::find($id)) {
                    TileImage::dispatch($image);
                }
            }
            $this->info('Dispatched '.count($actionable).' TileImage job(s).');
        }

        return self::SUCCESS;
    }
}
