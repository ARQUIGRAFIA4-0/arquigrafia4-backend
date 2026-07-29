<?php

namespace App\Jobs;

use App\Models\VRACore\VRACImage;
use App\Support\IiifInfo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Jcupitt\Vips;

class TileImage implements ShouldQueue
{
    use Queueable;

    /**
     * Retry transient failures (e.g. a busy disk) a few times before giving up.
     */
    public int $tries = 3;

    public int $backoff = 60;

    /**
     * Large originals can take a while to tile.
     */
    public int $timeout = 900;

    public function __construct(public VRACImage $image) {}

    /**
     * Build the IIIF tile pyramid for the image with libvips `dzsave`.
     *
     * The job is idempotent: it is safe to dispatch repeatedly. `processed_at`
     * is the canonical "tiles exist" marker — set it once the pyramid is on disk.
     */
    public function handle(): void
    {
        $original = $this->image->path('original', 'absolute');

        // Already tiled (info.json present): nothing to do. Make sure the DB
        // marker reflects reality, then return — this makes reruns free.
        if (file_exists($this->image->path('info', 'absolute'))) {
            if (is_null($this->image->processed_at)) {
                $this->image->update(['processed_at' => now()]);
            }

            return;
        }

        // No source original on disk (e.g. a legacy record whose file never
        // migrated). It can never be tiled — log once and stop retrying instead
        // of failing forever.
        if (! file_exists($original)) {
            Log::warning('TileImage: original missing, skipping', ['image_id' => $this->image->id]);

            return;
        }

        $image = Vips\Image::newFromFile($original, ['access' => 'sequential']);
        $image->dzsave($this->image->path('base', 'absolute'), [
            'layout' => 'iiif3',
            'id' => config('iiif.base_url', 'https://api-dev.arquigrafia.org.br/iiif'),
        ]);

        // libvips writes a minimal info.json with a baked-in id and no sizes.
        // Rewrite it from config so the host survives base-URL changes and add
        // the sizes array for whole-image derivative requests.
        IiifInfo::patch($this->image);

        $this->image->update(['processed_at' => now()]);
    }
}
