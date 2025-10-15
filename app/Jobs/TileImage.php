<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\VRACore\VRACImage;
use Jcupitt\Vips;

class TileImage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public VRACImage $image) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $original = storage_path($this->image->originalPath());
            $image = Vips\Image::newFromFile($original, ['access' => 'sequential']);
            $image->dzsave(storage_path($this->image->basePath()), [
                'layout' => 'iiif3',
                'id' => 'http://dev.arquigrafia.org/iiif'
            ]);
            $this->image->update(['isProcessing' => 'false']);
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
