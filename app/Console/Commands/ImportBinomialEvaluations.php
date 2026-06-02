<?php

namespace App\Console\Commands;

use App\Models\BinomialEvaluation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportBinomialEvaluations extends Command
{
    protected $signature = 'binomials:import {file : Ruta al archivo CSV}';

    protected $description = 'Importa evaluaciones de binomios desde un CSV del sistema legado';

    // Mapping de binomial_id viejo → nuevo
    private array $binomialMap = [
        21 => 1, // Aberta / Fechada
        20 => 2, // Interna / Externa
        19 => 3, // Complexa / Simples
        16 => 4, // Simétrica / Assimétrica
        14 => 5, // Translúcida / Opaca
        13 => 6, // Horizontal / Vertical
    ];

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("Archivo no encontrado: {$file}");
            return Command::FAILURE;
        }

        $this->info("Iniciando importación desde: {$file}");

        // Pre-cargar mapeos en memoria para mayor velocidad
        $this->info('Cargando mapeo de imágenes...');
        $imageMap = DB::table('vrac_images')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id');

        $this->info('Cargando mapeo de usuarios...');
        $userMap = DB::table('users')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id');

        $this->info("Imágenes mapeadas: {$imageMap->count()} | Usuarios mapeados: {$userMap->count()}");
        $this->newLine();

        $handle = fopen($file, 'r');
        fgetcsv($handle); // saltar header

        $imported        = 0;
        $skippedNoImage  = 0;
        $skippedNoUser   = 0;
        $skippedBinomial = 0;
        $total           = 0;

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            $total++;
            [$id, $photoId, $evaluationPosition, $binomialId, $userId] = $row;

            // Mapear binomial
            if (! isset($this->binomialMap[(int) $binomialId])) {
                $skippedBinomial++;
                $bar->advance();
                continue;
            }

            // Mapear imagen
            if (! isset($imageMap[(int) $photoId])) {
                $skippedNoImage++;
                $bar->advance();
                continue;
            }

            // Mapear usuario
            if (! isset($userMap[(int) $userId])) {
                $skippedNoUser++;
                $bar->advance();
                continue;
            }

            BinomialEvaluation::updateOrCreate(
                [
                    'image_id'    => $imageMap[(int) $photoId],
                    'user_id'     => $userMap[(int) $userId],
                    'binomial_id' => $this->binomialMap[(int) $binomialId],
                ],
                [
                    'value' => (int) $evaluationPosition,
                ]
            );

            $imported++;
            $bar->advance();
        }

        fclose($handle);
        $bar->finish();
        $this->newLine(2);

        $this->info('══════════════════════════════════════');
        $this->info("Total filas procesadas : {$total}");
        $this->info("Importadas             : {$imported}");
        $this->warn("Skipeadas (sin imagen) : {$skippedNoImage}");
        $this->warn("Skipeadas (sin usuario): {$skippedNoUser}");
        $this->warn("Skipeadas (sin binomio): {$skippedBinomial}");
        $this->info('══════════════════════════════════════');

        return Command::SUCCESS;
    }
}
