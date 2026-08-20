<?php

namespace App\Console\Commands;

use App\Models\Comment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyComments extends Command
{
    protected $signature = 'comments:import {file : Ruta al archivo CSV con comentarios válidos} {--dry-run : Simula la importación sin insertar datos}';

    protected $description = 'Importa comentarios desde un CSV del sistema legado';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("Archivo no encontrado: {$file}");
            return Command::FAILURE;
        }

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠  MODO DRY-RUN: no se insertará ningún dato');
        }

        $this->info("Iniciando importación desde: {$file}");

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
        $skippedExisting = 0;
        $skippedNoImage  = 0;
        $skippedNoUser   = 0;
        $total           = 0;

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            $total++;
            [$legacyId, $userId, $name, $photoId, $text, $postDate] = $row;

            if (! isset($imageMap[(int) $photoId])) {
                $skippedNoImage++;
                $bar->advance();
                continue;
            }

            if (! isset($userMap[(int) $userId])) {
                $skippedNoUser++;
                $bar->advance();
                continue;
            }

            $imageUuid = $imageMap[(int) $photoId];
            $userUuid  = $userMap[(int) $userId];
            $content   = trim($text);
            $createdAt = $postDate ?: now();

            $attributes = [
                'user_id'  => $userUuid,
                'image_id' => $imageUuid,
                'content'  => $content,
            ];

            if ($dryRun) {
                if (Comment::where($attributes)->exists()) {
                    $skippedExisting++;
                } else {
                    $imported++;
                }
            } else {
                $record = Comment::firstOrCreate(
                    $attributes,
                    [
                        'parent_id'  => null,
                        'is_deleted' => false,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $skippedExisting++;
                }
            }

            $bar->advance();
        }

        fclose($handle);
        $bar->finish();
        $this->newLine(2);

        $this->info('══════════════════════════════════════');
        if ($dryRun) {
            $this->warn('  RESULTADO DRY-RUN (nada fue insertado)');
        }
        $this->info("Total filas procesadas   : {$total}");
        $this->info("Insertadas               : {$imported}");
        $this->line("Ya existían (preservadas): {$skippedExisting}");
        $this->warn("Skipeadas (sin imagen)   : {$skippedNoImage}");
        $this->warn("Skipeadas (sin usuario)  : {$skippedNoUser}");
        $this->info('══════════════════════════════════════');

        return Command::SUCCESS;
    }
}
