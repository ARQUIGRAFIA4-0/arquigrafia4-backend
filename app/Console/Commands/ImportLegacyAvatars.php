<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Jcupitt\Vips\Image as VipsImage;
use Symfony\Component\Process\Process;

/**
 * Migrates legacy avatars matching the simple `/arquigrafia-avatars/{id}.jpg`
 * pattern into accounts already linked via `users.legacy_id`.
 *
 * Does NOT cover the `/profile/{x}/showphotoprofile/{uuid}/` pattern — that
 * source is still unresolved and left for a follow-up.
 *
 * Mirrors the temp-database approach used by TransformLegacy.
 */
class ImportLegacyAvatars extends Command
{
    protected $signature = 'avatars:import-legacy
        {input : Path to the legacy SQL dump (mysqldump output)}
        {avatars-dir : Local directory with the legacy avatar files, named {legacy_id}.jpg}
        {--dry-run : Preview without writing anything}
        {--only-legacy-user-id= : Only process this legacy users.id}
        {--force : Overwrite avatar_path even if already set}
        {--db-host=mysql : MySQL host used to load the dump}
        {--db-port=3306 : MySQL port}
        {--db-user=root : MySQL user with CREATE/DROP DATABASE privileges}
        {--db-password=password : MySQL password}
        {--keep-db : Do not drop the temporary database after the run}';

    protected $description = 'Migrate legacy avatars (/arquigrafia-avatars/{id}.jpg pattern) for accounts already linked via legacy_id';

    public function handle(): int
    {
        $inputPath = $this->argument('input');
        if (! file_exists($inputPath)) {
            $this->error("Input file not found: {$inputPath}");

            return self::FAILURE;
        }

        $avatarsDir = rtrim($this->argument('avatars-dir'), '/');
        if (! is_dir($avatarsDir)) {
            $this->error("Avatars directory not found: {$avatarsDir}");

            return self::FAILURE;
        }

        $dbName = 'arquigrafia_avatars_'.now()->format('YmdHis');
        $this->createDatabase($dbName);

        try {
            $this->loadDump($inputPath, $dbName);
            $this->configureConnection($dbName);

            $rows = $this->fetchLegacyAvatars();
            $this->info('Fetched '.count($rows).' legacy avatar rows (photo LIKE /arquigrafia-avatars/%.jpg).');

            [$updated, $skippedNoUser, $skippedHasAvatar, $skippedNoFile, $errors] = $this->processRows($rows);

            $this->newLine();
            $this->info("Done. updated={$updated} skipped_no_user={$skippedNoUser} skipped_has_avatar={$skippedHasAvatar} skipped_no_file={$skippedNoFile} errors={$errors}");
        } finally {
            if (! $this->option('keep-db')) {
                $this->dropDatabase($dbName);
            } else {
                $this->info("Temporary database `{$dbName}` left in place (--keep-db).");
            }
        }

        return self::SUCCESS;
    }

    private function fetchLegacyAvatars(): array
    {
        $onlyId = $this->option('only-legacy-user-id');

        $query = DB::connection('legacy_dump')
            ->table('users')
            ->select('id', 'photo')
            ->where('photo', 'LIKE', '/arquigrafia-avatars/%.jpg');

        if ($onlyId) {
            $query->where('id', $onlyId);
        }

        return $query->get()->all();
    }

    /**
     * @return array{0:int,1:int,2:int,3:int,4:int} [updated, skipped_no_user, skipped_has_avatar, skipped_no_file, errors]
     */
    private function processRows(array $rows): array
    {
        $updated = $skippedNoUser = $skippedHasAvatar = $skippedNoFile = $errors = 0;
        $avatarsDir = rtrim($this->argument('avatars-dir'), '/');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $legacyId = (int) $row->id;

            $user = User::where('legacy_id', $legacyId)->first();
            if (! $user) {
                $skippedNoUser++;
                $bar->advance();

                continue;
            }

            if ($user->avatar_path && ! $force) {
                $skippedHasAvatar++;
                $bar->advance();

                continue;
            }

            $sourcePath = "{$avatarsDir}/{$legacyId}.jpg";
            if (! file_exists($sourcePath)) {
                $skippedNoFile++;
                $bar->advance();
                $this->newLine();
                $this->warn("Missing file for legacy_id={$legacyId}: {$sourcePath}");
                $bar->display();

                continue;
            }

            if ($dryRun) {
                $updated++;
                $bar->advance();

                continue;
            }

            try {
                $this->processAvatar($user, $sourcePath);
                $updated++;
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->warn("Failed legacy_id={$legacyId}: ".$e->getMessage());
                $bar->display();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return [$updated, $skippedNoUser, $skippedHasAvatar, $skippedNoFile, $errors];
    }

    private function processAvatar(User $user, string $sourcePath): void
    {
        $filename = 'avatars/users/'.$user->id.'.webp';
        $destPath = Storage::disk('public')->path($filename);

        if (! is_dir(dirname($destPath))) {
            mkdir(dirname($destPath), 0775, true);
        }

        $image = VipsImage::newFromFile($sourcePath, ['access' => 'sequential']);

        $width = $image->width;
        $height = $image->height;
        $minSide = min($width, $height);

        $image = $image->crop(
            (int) (($width - $minSide) / 2),
            (int) (($height - $minSide) / 2),
            $minSide,
            $minSide
        );

        $image = $image->thumbnail_image(400, [
            'height' => 400,
            'size' => 'force',
        ]);

        $image->writeToFile($destPath, [
            'Q' => 82,
            'strip' => true,
        ]);

        $user->avatar_path = $filename;
        $user->save();
    }

    // ---------------------------------------------------------------------
    // MySQL temp DB (mirrors TransformLegacy.php)
    // ---------------------------------------------------------------------

    private function mysqlArgs(): array
    {
        $args = [
            '-h', $this->option('db-host'),
            '-P', (string) $this->option('db-port'),
            '-u', $this->option('db-user'),
        ];
        $password = $this->option('db-password');
        if ($password !== null && $password !== '') {
            $args[] = '-p'.$password;
        }

        return $args;
    }

    private function createDatabase(string $name): void
    {
        $this->info("Creating temporary database `{$name}`...");
        $args = array_merge(['mysql'], $this->mysqlArgs(),
            ['-e', "CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"]);
        $this->runProcess($args);
    }

    private function dropDatabase(string $name): void
    {
        $this->info("Dropping temporary database `{$name}`...");
        $args = array_merge(['mysql'], $this->mysqlArgs(), ['-e', "DROP DATABASE IF EXISTS `{$name}`"]);
        $this->runProcess($args);
    }

    private function loadDump(string $dumpPath, string $dbName): void
    {
        $this->info("Loading {$dumpPath} into `{$dbName}` (this can take a minute)...");
        $args = array_merge(['mysql'], $this->mysqlArgs(), [$dbName]);
        $process = new Process($args);
        $process->setTimeout(null);
        $process->setInput(fopen($dumpPath, 'r'));
        $process->mustRun();
    }

    private function runProcess(array $args): void
    {
        $process = new Process($args);
        $process->setTimeout(null);
        $process->mustRun();
    }

    private function configureConnection(string $dbName): void
    {
        config(['database.connections.legacy_dump' => [
            'driver' => 'mysql',
            'host' => $this->option('db-host'),
            'port' => (int) $this->option('db-port'),
            'database' => $dbName,
            'username' => $this->option('db-user'),
            'password' => (string) $this->option('db-password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'strict' => false,
        ]]);
        DB::purge('legacy_dump');
    }
}
