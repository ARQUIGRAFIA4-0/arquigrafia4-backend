<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Image;
use Jcupitt\Vips;

class TileImage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Image $image) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $path = $this->image->file_path;
            $image = Vips\Image::newFromFile($path.'/full/max/0/default.jpg', ['access' => 'sequential']);
            $image->dzsave($path, [
                'layout' => 'iiif3',
                'id' => 'http://dev.arquigrafia.org/iiif'
            ]);
            $this->image->update(['isProcessing' => 'false']);
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
