<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\Location;
use Symfony\Component\Process\Process;

class GenerateLocationPmtiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $disk;
    protected string $geojsonPath;
    protected string $outputPath;
    protected array $options;

    /**
     * Create a new job instance.
     *
     * @param string $disk Storage disk where geojson is read/written (default public)
     * @param string $geojsonPath Path to geojson inside the disk (if empty, will be generated)
     * @param string $outputPath Path for resulting pmtiles inside the disk
     * @param array $options Additional tippecanoe options
     */
    public function __construct(
        string $disk = 'public',
        string $geojsonPath = 'locations/locations.geojson',
        string $outputPath = 'tiles/locations.pmtiles',
        array $options = []
    ) {
        $this->disk = $disk;
        $this->geojsonPath = $geojsonPath;
        $this->outputPath = $outputPath;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Ensure geojson exists: if not, generate it using the Location model exporter
        $disk = $this->disk;
        if (! Storage::disk($disk)->exists($this->geojsonPath)) {
            Location::exportGeoJson($disk, $this->geojsonPath);
        }

        $inputAbs = Storage::disk($disk)->path($this->geojsonPath);
        $outputAbs = Storage::disk($disk)->path($this->outputPath);

        // Ensure output directory exists
        $outDir = dirname($outputAbs);
        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        // Build tippecanoe command. We prefer an array form to avoid shell escaping issues.
        $baseCmd = [
            'tippecanoe',
            '-zg',
            '--output=' . $outputAbs,
            '--force',
            '--read-parallel',
        ];

        // Append any user-provided options
        foreach ($this->options as $opt => $val) {
            if (is_int($opt)) {
                // numeric keys mean option is a raw flag or flag+value
                $baseCmd[] = (string) $val;
            } else {
                $baseCmd[] = (string) $opt . '=' . (string) $val;
            }
        }

        // Finally, add the input file
        $baseCmd[] = $inputAbs;

        // Run the process
        $process = new Process($baseCmd);
        // Give tippecanoe some time for large datasets
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            // cleanup partial output if present
            if (file_exists($outputAbs)) {
                @unlink($outputAbs);
            }

            throw new \RuntimeException('tippecanoe failed: ' . $process->getErrorOutput() . PHP_EOL . $process->getOutput());
        }

        // Successfully generated file. Nothing else to do here; consumers can access via Storage::disk($disk)->path/->url
    }
}
