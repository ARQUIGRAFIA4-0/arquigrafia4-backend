<?php

namespace App\Console\Commands;

use App\Models\VRACore\VRACImage;
use App\Jobs\TileImage;
use Illuminate\Console\Command;
use Jcupitt\Vips\Image as VipsImage;

class ProcessMigratedImages extends Command
{
    protected $signature = 'images:process-migrated {--limit=0 : Limit number of images to process (0 = all)} {--id= Process specific image}';
    protected $description = 'Process migrated images: copy files, create derivatives, and tile';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $id = 	$this->option('id');
        $query = VRACImage::query();

        if ($id) {
	    $query->where('id', $id);
	} elseif ($limit > 0) {
            $query->limit($limit);
        }

        $images = $query->get();
        $total = $images->count();

        $this->info("Processing {$total} images...");

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        foreach ($images as $image) {
            try {
                $progressBar->clear();
                $this->line("Processing image {$image->id}...");
                $progressBar->display();

                $this->processImage($image);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("\nError processing image {$image->id}: " . $e->getMessage());
                $progressBar->advance();
                continue;
            }
        }

        $progressBar->finish();
        $this->info("\n✅ All images processed!");

        return Command::SUCCESS;
    }

    protected function processImage(VRACImage $image): void
    {
        $this->createDerivatives($image);
        TileImage::dispatch($image);
    }

    protected function createDerivatives(VRACImage $image): void
    {
	$this->info($image->path('original', 'absolute'));
        $original = VipsImage::newFromFile($image->path('original', 'absolute'), ['access' => 'sequential']);
        $origWidth = $original->width ?? null;
        $origHeight = $original->height ?? null;

        $thumbnail = $thumbnail = VipsImage::thumbnail($image->path('original', 'absolute'), 300);
        $thumbWidth = $thumbnail->width;
        $thumbHeight = $thumbnail->height;
        $thumbDest = $image->path('thumb', 'absolute', ['width' => $thumbWidth, 'height' => $thumbHeight]);
        if (!file_exists(dirname($thumbDest))) {
            mkdir(dirname($thumbDest), 0755, true);
        }
        $thumbnail->writeToFile($thumbDest);

        $mid = VipsImage::thumbnail($image->path('original', 'absolute'), 1024);
        $midWidth = $mid->width;
        $midHeight = $mid->height;
        $midDest = $image->path('mid', 'absolute', ['width' => $midWidth, 'height' => $midHeight]);
        if (!file_exists(dirname($midDest))) {
            mkdir(dirname($midDest), 0755, true);
        }
        $mid->writeToFile($midDest);

        $image->update([
            'sizes' => [
                'original' => [
                    'width' => $origWidth ?? null,
                    'height' => $origHeight ?? null,
                ],
                'mid' => [
                    'width' => $midWidth ?? null,
                    'height' => $midHeight ?? null,
                ],
                'thumb' => [
                    'width' => $thumbWidth ?? null,
                    'height' => $thumbHeight ?? null,
                ],
            ],
        ]);

    }
}
