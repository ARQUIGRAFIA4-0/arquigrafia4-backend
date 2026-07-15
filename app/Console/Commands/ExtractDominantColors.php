<?php

namespace App\Console\Commands;

use App\Models\VRACore\VRACImage;
use Illuminate\Console\Command;
use Jcupitt\Vips\Image as VipsImage;

class ExtractDominantColors extends Command
{
    protected $signature = 'images:extract-colors
                            {--limit=0 : Limit number of images to process (0 = all)}
                            {--force : Reprocess images that already have a dominant color}';

    protected $description = 'Extract and store the dominant color for each image';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');

        $query = VRACImage::query();

        if (!$force) {
            $query->whereNull('dominant_color');
        }

        $total = (clone $query)->count();

        if ($limit > 0) {
            $total = min($total, $limit);
        }

        $this->info("Processing {$total} images...");

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $processed = 0;
        $failed = 0;

        $query->orderBy('created_at')->chunkById(100, function ($images) use (
            $progressBar, &$processed, &$failed, $limit
        ) {
            foreach ($images as $image) {
                if ($limit > 0 && ($processed + $failed) >= $limit) {
                    return false;
                }

                $path = $image->path('original', 'absolute');

                if (!file_exists($path)) {
                    $progressBar->clear();
                    $this->line("File not found: {$image->id}");
                    $progressBar->display();
                    $failed++;
                    $progressBar->advance();
                    continue;
                }

                try {
                    $tiny = VipsImage::thumbnail($path, 1, ['height' => 1]);
                    $pixel = $tiny->getpoint(0, 0);
                    $hex = sprintf('#%02x%02x%02x',
                        (int) min(255, max(0, $pixel[0] ?? 0)),
                        (int) min(255, max(0, $pixel[1] ?? 0)),
                        (int) min(255, max(0, $pixel[2] ?? 0)),
                    );

                    $image->dominant_color = $hex;
                    $image->save();
                    $processed++;
                } catch (\Exception $e) {
                    $progressBar->clear();
                    $this->line("Error {$image->id}: " . $e->getMessage());
                    $progressBar->display();
                    $failed++;
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine();
        $this->info("Done: {$processed} processed, {$failed} failed.");

        return Command::SUCCESS;
    }
}
