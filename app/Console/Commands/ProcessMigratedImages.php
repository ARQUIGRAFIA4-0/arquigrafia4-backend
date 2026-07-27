<?php

namespace App\Console\Commands;

use App\Jobs\TileImage;
use App\Models\VRACore\VRACImage;
use Illuminate\Console\Command;
use Jcupitt\Vips\Image as VipsImage;

class ProcessMigratedImages extends Command
{
    protected $signature = 'images:process-migrated
        {--limit=0 : Limit number of images to process (0 = all)}
        {--id= : Process specific image}
        {--force : Reprocess even if already tiled}';

    protected $description = 'Process migrated images: create derivatives and tile (idempotent — skips already-tiled images and images with no original)';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $id = $this->option('id');
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

        $tiled = 0;
        $skippedTiled = 0;
        $skippedNoOriginal = 0;
        $errors = 0;

        foreach ($images as $image) {
            try {
                // Skip images that already have a tile pyramid, unless forced.
                if (! $this->option('force') && $this->isTiled($image)) {
                    $skippedTiled++;
                    $progressBar->advance();

                    continue;
                }

                // Cannot tile without the source original on disk.
                if (! file_exists($image->path('original', 'absolute'))) {
                    $skippedNoOriginal++;
                    $progressBar->advance();

                    continue;
                }

                $progressBar->clear();
                $this->line("Processing image {$image->id}...");
                $progressBar->display();

                $this->processImage($image);
                $tiled++;
                $progressBar->advance();
            } catch (\Exception $e) {
                $errors++;
                $this->error("\nError processing image {$image->id}: ".$e->getMessage());
                $progressBar->advance();

                continue;
            }
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Done. dispatched={$tiled} skipped_already_tiled={$skippedTiled} skipped_no_original={$skippedNoOriginal} errors={$errors}");

        return Command::SUCCESS;
    }

    /**
     * An image is considered tiled when its IIIF pyramid's info.json exists on disk.
     */
    protected function isTiled(VRACImage $image): bool
    {
        return file_exists($image->path('info', 'absolute'));
    }

    protected function processImage(VRACImage $image): void
    {
        // Only (re)generate thumb/mid derivatives when they are missing. The
        // backfill of previously-migrated images already has `sizes` populated
        // and derivative files on disk, so the expensive step there is tiling.
        if ($this->option('force') || empty($image->sizes)) {
            $this->createDerivatives($image);
        }

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
        if (! file_exists(dirname($thumbDest))) {
            mkdir(dirname($thumbDest), 0775, true);
        }
        $thumbnail->writeToFile($thumbDest);

        $mid = VipsImage::thumbnail($image->path('original', 'absolute'), 1024);
        $midWidth = $mid->width;
        $midHeight = $mid->height;
        $midDest = $image->path('mid', 'absolute', ['width' => $midWidth, 'height' => $midHeight]);
        if (! file_exists(dirname($midDest))) {
            mkdir(dirname($midDest), 0775, true);
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
