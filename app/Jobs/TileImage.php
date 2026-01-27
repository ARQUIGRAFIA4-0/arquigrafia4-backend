<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
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
            $image = Vips\Image::newFromFile($this->image->path('original','absolute'), ['access' => 'sequential']);
            $image->dzsave($this->image->path('base', 'absolute'), [ // baseAbsolutePath()
                'layout' => 'iiif3',
                'id' => 'http://dev.arquigrafia.org/iiif'
            ]);
            $this->image->update(['isProcessing' => 'false']);
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
