<?php

namespace App\Console\Commands;

use App\Models\Collective;
use App\Models\Location;
use App\Models\Profile;
use App\Models\User;
use App\Models\VRACore\VRACAgent;
use App\Models\VRACore\VRACAgentRole;
use App\Models\VRACore\VRACContributorName;
use App\Models\VRACore\VRACDate;
use App\Models\VRACore\VRACDescription;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACRight;
use App\Models\VRACore\VRACSubject;
use App\Models\VRACore\VRACTechnique;
use App\Models\VRACore\VRACTitle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Imports a legacy SQL dump into the new VRA Core schema.
 *
 * Replaces the OpenRefine + LegacyCSVSeeder flow with a single command:
 *   1. Load the dump into a throwaway MySQL database.
 *   2. Join `photos` × `tags` × `authors` to reconstruct the OpenRefine input.
 *   3. Reapply every meaningful transform from history.json (tags/authors
 *      normalization, date parsing, license matrix, etc.).
 *   4. Insert rows into VRACImage and related VRA Core tables on the app's
 *      default connection, looking up users via `users.legacy_id`.
 *   5. Emit a convenience CSV of what was processed.
 *
 * Faulty CSV-quoting fixes from history.json are intentionally skipped — we
 * read straight from MySQL so the quoting issues never arise.
 *
 * Skipped on purpose:
 *   - geocoding (ops 259–278) — assume lat/lon are already in `photos`-like data
 *   - VCAA tag reconciliation (ops 283–287) — handled by sync:tematres + DB match
 */
class TransformLegacy extends Command
{
    protected $signature = 'transform:legacy
        {input : Path to the legacy SQL dump (mysqldump output)}
        {--csv= : Optional path to also write a CSV mirror of the processed image rows}
        {--db-host=mysql : MySQL host used to load the dump}
        {--db-port=3306 : MySQL port}
        {--db-user=root : MySQL user with CREATE/DROP DATABASE privileges}
        {--db-password=password : MySQL password}
        {--keep-db : Do not drop the temporary database after the run}
        {--dry-run : Run the transforms but do not write anything to the app database}
        {--skip-users : Skip the user-migration phase (assume users.legacy_id is already populated)}
        {--skip-images : Skip the image-migration phase (only migrate users)}';

    protected $description = 'Load a legacy SQL dump, migrate new users, then seed VRACore tables from the legacy photos';

    private const COLLECTIVE_MAP = [
        '1' => 'a0d427a6-b8d8-42db-a9d9-4fc02ae8c4c5',
        '2' => '2a39bf3d-f6b3-482e-ab22-5edfd9f1f06f',
        '3' => 'ae9e2da4-afb5-4d00-8f6b-579333801189',
        '4' => '70b8032b-0f23-41d2-8a59-0fdd1f898ca3',
        '5' => '26145bc0-f632-4a30-9cfd-3e9a20de0cc9',
        '6' => 'fe1e81b5-bc6a-43ed-9bdd-4ca3cbfaab85',
        '7' => '8a2c1a68-260b-41fc-a067-3259b4601bd8',
        '8' => 'c02bb389-895c-4962-98fa-ed58f9ba0535',
    ];

    private array $tagsMap = [];
    private array $authorsMap = [];
    private array $agentMap = [];

    private array $userIdCache = [];
    private array $existingCollectiveIds = [];
    private array $subjectIndex = [];
    private ?VRACAgentRole $photographerRole = null;
    private ?VRACTechnique $digitalImageTechnique = null;

    public function handle(): int
    {
        $inputPath = $this->argument('input');
        if (! File::exists($inputPath)) {
            $this->error("Input file not found: {$inputPath}");
            return self::FAILURE;
        }

        $this->loadNormalizations();

        $dbName = 'arquigrafia_legacy_'.now()->format('YmdHis');
        $this->createDatabase($dbName);

        try {
            $this->loadDump($inputPath, $dbName);
            $this->configureConnection($dbName);

            if (! $this->option('skip-users')) {
                $this->runUsersPhase();
            }

            if (! $this->option('skip-images')) {
                $this->runImagesPhase();
            }
        } finally {
            if (! $this->option('keep-db')) {
                $this->dropDatabase($dbName);
            } else {
                $this->info("Temporary database `{$dbName}` left in place (--keep-db).");
            }
        }

        return self::SUCCESS;
    }

    private function runUsersPhase(): void
    {
        $this->info('--- Phase 1: users ---');
        $existing = User::pluck('id', 'legacy_id')->all();
        $this->info('Found '.count($existing).' existing users with a legacy_id.');

        $rows = DB::connection('legacy_dump')
            ->table('users')
            ->select('id', 'name', 'lastName', 'email', 'password', 'active',
                'gender', 'birthday', 'scholarity', 'city', 'state', 'country',
                'created_at', 'updated_at')
            ->get();
        $this->info('Fetched '.count($rows).' users from the dump.');

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();
        $created = $skipped = 0;

        foreach ($rows as $row) {
            $legacyId = (int) $row->id;

            if (isset($existing[$legacyId])) {
                $skipped++;
                $bar->advance();
                continue;
            }
            if (! $this->validUserRow($row)) {
                $skipped++;
                $bar->advance();
                continue;
            }
            if (! $this->option('dry-run')) {
                try {
                    $userId = $this->createLegacyUser($row);
                    $existing[$legacyId] = $userId;
                    $this->userIdCache[(string) $legacyId] = $userId;
                    $created++;
                } catch (\Throwable $e) {
                    $skipped++;
                    $this->newLine();
                    $this->warn("User legacy_id={$legacyId} failed: ".$e->getMessage());
                }
            } else {
                $created++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Users: created {$created}, skipped {$skipped} (already-present or invalid).");
    }

    private function runImagesPhase(): void
    {
        $this->info('--- Phase 2: images ---');

        if (! $this->option('dry-run')) {
            $this->primeAppLookups();
        }

        $rows = $this->fetchPhotos();
        $this->info('Fetched '.count($rows).' rows from `photos` (joined with tags + authors).');

        [$processed, $imported, $skipped] = $this->processRows($rows);
        $this->info("Images: processed {$processed}, imported {$imported}, skipped {$skipped}.");
    }

    private function validUserRow(object $row): bool
    {
        $email = trim((string) ($row->email ?? ''));
        if ($email === '' || strtoupper($email) === 'NULL') {
            return false;
        }
        // Skip rows whose email clashes with an existing user — emails are UNIQUE.
        if (User::where('email', $email)->exists()) {
            return false;
        }
        return true;
    }

    private function createLegacyUser(object $row): string
    {
        $first = trim((string) ($row->name ?? ''));
        $last = $this->nullableString($row->lastName ?? null);
        $fullName = Str::ucwords(Str::lower(trim($first.($last ? ' '.$last : ''))));
        $userId = (string) Str::uuid();

        DB::transaction(function () use ($row, $userId, $fullName) {
            $user = new User();
            $user->id = $userId;
            $user->name = Str::limit($fullName, 60, '');
            $user->email = trim((string) $row->email);
            $user->password = ! empty($row->password)
                ? (string) $row->password
                : Hash::make('UmaSenhaSimples');
            $user->legacy_id = (int) $row->id;
            $user->email_verified_at = strtolower((string) ($row->active ?? '')) === 'yes' ? now() : null;
            if (! empty($row->created_at)) {
                $user->created_at = $this->parseTimestamp((string) $row->created_at);
            }
            if (! empty($row->updated_at)) {
                $user->updated_at = $this->parseTimestamp((string) $row->updated_at);
            }
            $user->save();

            $profile = new Profile();
            $profile->user_id = $userId;
            $configurations = [];
            if ($gender = $this->nullableString($row->gender ?? null)) {
                $profile->gender = Str::substr($gender, 0, 20);
                $configurations['gender'] = false;
            }
            if ($birthday = $this->parseTimestamp($this->nullableString($row->birthday ?? null))) {
                $profile->birthdate = $birthday;
                $configurations['birthdate'] = false;
            }
            if ($scholarity = $this->nullableString($row->scholarity ?? null)) {
                $profile->scholarity = Str::substr($scholarity, 0, 20);
                $configurations['scholarity'] = false;
            }
            $city = $this->nullableString($row->city ?? null);
            $state = $this->nullableString($row->state ?? null);
            $country = $this->nullableString($row->country ?? null);
            if ($city || $state || $country) {
                $profile->address = Str::substr(trim("{$city} {$state} {$country}"), 0, 255);
                $configurations['address'] = false;
            }
            $profile->configurations = $configurations;
            $profile->save();

            $contributor = new VRACContributorName();
            $contributor->name = $fullName;
            $contributor->type = 'personal';
            $contributor->vocab = 'ARQUIGRAFIA';
            $contributor->ref_id = $userId;
            $contributor->user_id = $userId;
            $contributor->save();
        });

        return $userId;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) return null;
        $v = trim((string) $value);
        if ($v === '' || strtoupper($v) === 'NULL') return null;
        return $v;
    }

    // ---------------------------------------------------------------------
    // Setup
    // ---------------------------------------------------------------------

    private function loadNormalizations(): void
    {
        $path = database_path('seeders/data/legacy-transform-normalizations.json');
        $data = json_decode(File::get($path), true);
        $this->tagsMap = $data['tags'] ?? [];
        $this->authorsMap = $data['imageAuthor'] ?? [];
        $this->agentMap = $data['VRA_ImageAgent'] ?? [];
    }

    private function primeAppLookups(): void
    {
        $this->photographerRole = VRACAgentRole::firstOrCreate(
            ['label' => 'fotógrafo'],
            ['id' => (string) Str::uuid()]
        );

        $this->digitalImageTechnique = VRACTechnique::firstOrCreate(
            [
                'label' => 'Imagem digital',
                'vocab' => 'VCAA',
                'ref_id' => '4269',
            ],
            ['id' => (string) Str::uuid()]
        );

        VRACSubject::chunk(500, function ($rows) {
            foreach ($rows as $r) {
                $this->subjectIndex[$r->term] = $r;
            }
        });

        // Set of locally-existing collective UUIDs, used to filter out
        // COLLECTIVE_MAP entries that point at collectives we haven't seeded.
        $this->existingCollectiveIds = Collective::pluck('id')->flip()->all();
    }

    // ---------------------------------------------------------------------
    // MySQL temp DB
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

    /**
     * Pull rows out of the dumped `photos` table, aggregating the related
     * `tags` and `authors` rows into comma/semicolon separated strings —
     * the exact shape OpenRefine started from.
     */
    private function fetchPhotos(): array
    {
        $sql = <<<'SQL'
            SELECT
                p.*,
                (
                    SELECT GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ', ')
                    FROM tag_assignments ta
                    JOIN tags t ON t.id = ta.tag_id
                    WHERE ta.photo_id = p.id
                ) AS tags,
                (
                    SELECT GROUP_CONCAT(a.name ORDER BY a.name SEPARATOR '; ')
                    FROM photo_author pa
                    JOIN authors a ON a.id = pa.author_id
                    WHERE pa.photo_id = p.id
                ) AS authors
            FROM photos p
        SQL;

        return array_map(
            fn ($row) => (array) $row,
            DB::connection('legacy_dump')->select($sql)
        );
    }

    // ---------------------------------------------------------------------
    // Row processing
    // ---------------------------------------------------------------------

    /**
     * @return array{0:int,1:int,2:int} [processed, imported, skipped]
     */
    private function processRows(array $rows): array
    {
        $csvFh = null;
        if ($csvPath = $this->option('csv')) {
            $csvFh = fopen($csvPath, 'w');
            fputcsv($csvFh, $this->csvColumns());
        }

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        $processed = $imported = $skipped = 0;

        foreach ($rows as $raw) {
            $processed++;
            $row = $this->transformRow($raw);

            if ($row === null) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if ($csvFh) {
                fputcsv($csvFh, array_map(
                    fn ($c) => $row[$c] ?? '',
                    $this->csvColumns()
                ));
            }

            if (! $this->option('dry-run')) {
                try {
                    $this->importRow($row);
                    $imported++;
                } catch (\Throwable $e) {
                    $skipped++;
                    $this->newLine();
                    $this->warn("Row legacy_id={$row['id']} failed: ".$e->getMessage());
                }
            } else {
                $imported++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($csvFh) {
            fclose($csvFh);
            $this->info('Wrote CSV mirror to '.$this->option('csv'));
        }

        return [$processed, $imported, $skipped];
    }

    /**
     * Apply every meaningful op from history.json to a single row.
     * Returns null if the row should be dropped (deleted == 1).
     */
    private function transformRow(array $r): ?array
    {
        if ((string) ($r['deleted'] ?? '') === '1') {
            return null;
        }

        $out = [];
        $out['id'] = $r['id'];

        // Tags pipeline (ops 0–3, 50–52, 280, 288, 289)
        $out['tags'] = $this->processTags($r['tags'] ?? '');

        // Dates (ops 4–33, 53–58)
        [$earliest, $latest, $circa] = $this->processDates($r['dataCriacao'] ?? '');
        $out['VRA_EarliestDate'] = $earliest;
        $out['VRA_LatestDate'] = $latest;
        $out['VRA_DateCirca'] = $circa;

        // Renames (ops 34, 35, 71–72, 107–109)
        $out['VRA_Title'] = (string) ($r['name'] ?? '');
        $out['VRA_Description'] = (string) ($r['description'] ?? '');
        $out['VRA_Location_Refid'] = null; // op 70 nulls `tombo`

        // imageAuthor pipeline (ops 36–49) + agent canonicalization (op 108)
        $contributor = $this->processAuthors($r['imageAuthor'] ?? '');
        $out['VRA_ImageContributor'] = $this->applyMap($contributor, $this->agentMap);

        // License matrix (ops 63–69)
        $out['VRA_Right'] = $this->licenseFor(
            (string) ($r['allowCommercialUses'] ?? ''),
            (string) ($r['allowModifications'] ?? '')
        );

        // Address: NULL → null (ops 257, 258, 262–266)
        foreach (['street', 'district', 'city', 'state', 'country'] as $col) {
            $val = $r[$col] ?? null;
            $out[$col] = ($val === 'NULL' || $val === null) ? null : $val;
        }
        $out['complete_address'] = $this->joinNonEmpty(
            [$out['street'], $out['district'], $out['city'], $out['state'], $out['country']],
            ', '
        );

        // Geocoding columns left blank — user fills these later
        $out['latitude'] = '';
        $out['longitude'] = '';

        // Pass-through columns
        foreach (['nome_arquivo', 'user_id', 'institution_id', 'created_at', 'updated_at', 'deleted_at', 'workdate'] as $col) {
            $out[$col] = $r[$col] ?? null;
        }

        // Collapse newlines/tabs and trim every string value so the output CSV
        // is single-line per record and downstream consumers don't see stray whitespace.
        foreach ($out as $k => $v) {
            if (is_string($v)) {
                $out[$k] = $this->collapseWhitespace($v);
            }
        }

        return $out;
    }

    private function collapseWhitespace(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', $s) ?? '');
    }

    /**
     * Mirror of the LegacyCSVSeeder write path, fed directly from in-memory
     * transformed rows (no CSV round-trip).
     */
    private function importRow(array $data): void
    {
        $userUuid = $this->lookupUserUuid((string) ($data['user_id'] ?? ''));
        if (! $userUuid) {
            throw new \RuntimeException("no users.legacy_id match for user_id={$data['user_id']}");
        }

        DB::transaction(function () use ($data, $userUuid) {
            $image = VRACImage::withTrashed()->firstOrNew(['legacy_id' => $data['id']]);
            if (! $image->exists) {
                $image->id = (string) Str::uuid();
            }
            $institutionId = trim((string) ($data['institution_id'] ?? ''));
            $image->fill([
                'user_id' => $userUuid,
                'collective_id' => $this->resolveCollectiveId($institutionId),
                'created_at' => $this->parseTimestamp($data['created_at'] ?? null),
                'updated_at' => $this->parseTimestamp($data['updated_at'] ?? null),
                'deleted_at' => $this->parseTimestamp($data['deleted_at'] ?? null),
            ]);
            $image->save();

            // Title
            if (($title = trim((string) $data['VRA_Title'])) !== '') {
                $rec = VRACTitle::firstOrCreate(
                    ['label' => $title],
                    ['id' => (string) Str::uuid(), 'type' => 'other']
                );
                $image->titles()->syncWithoutDetaching($rec->id);
            }

            // Description
            if (($desc = trim((string) $data['VRA_Description'])) !== '') {
                $rec = VRACDescription::firstOrCreate(
                    ['text' => $desc],
                    ['id' => (string) Str::uuid()]
                );
                $image->descriptions()->syncWithoutDetaching($rec->id);
            }

            // Dates
            $earliest = $data['VRA_EarliestDate'] === '' ? null : $data['VRA_EarliestDate'];
            $latest = $data['VRA_LatestDate'] === '' ? null : $data['VRA_LatestDate'];
            $circaBool = $data['VRA_DateCirca'] === 'true' ? 1 : 0;
            if ($earliest !== null || $latest !== null) {
                $rec = VRACDate::firstOrCreate(
                    [
                        'type' => 'creation',
                        'earliest_date' => $earliest,
                        'latest_date' => $latest,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'circa_earliest_date' => $circaBool,
                        'circa_latest_date' => $circaBool,
                    ]
                );
                $image->dates()->syncWithoutDetaching($rec->id);
            }

            // Subjects (tags)
            $tagsRaw = trim((string) $data['tags']);
            if ($tagsRaw !== '') {
                foreach (array_filter(array_map('trim', explode(',', $tagsRaw))) as $term) {
                    $vocab = isset($this->subjectIndex[$term]) ? 'VCAA' : 'Arquigrafia';
                    $sub = VRACSubject::firstOrCreate(
                        ['term' => $term],
                        ['id' => (string) Str::uuid(), 'vocab' => $vocab]
                    );
                    $this->subjectIndex[$term] = $sub;
                    $image->subjects()->syncWithoutDetaching($sub->id);
                }
            }

            // Rights
            if (($license = trim((string) $data['VRA_Right'])) !== '') {
                $right = VRACRight::firstOrCreate(
                    ['href' => VRACRight::getLicenseMap()[$license] ?? null],
                    [
                        'text' => '',
                        'id' => (string) Str::uuid(),
                        'type' => 'other',
                        'rights_holder' => '',
                    ]
                );
                $image->rights()->syncWithoutDetaching($right->id);
            }

            // Agent (image contributor)
            if (($contributor = trim((string) $data['VRA_ImageContributor'])) !== '') {
                $contrib = VRACContributorName::firstOrCreate(
                    ['name' => $contributor],
                    ['id' => (string) Str::uuid(), 'type' => 'personal']
                );
                $agent = VRACAgent::firstOrCreate(
                    [
                        'contributor_name_id' => $contrib->id,
                        'role_id' => $this->photographerRole->id,
                    ],
                    ['id' => (string) Str::uuid()]
                );
                $image->agents()->syncWithoutDetaching($agent->id);
            }

            // Location
            $lat = trim((string) $data['latitude']);
            $lng = trim((string) $data['longitude']);
            $label = trim((string) $data['complete_address']);
            if ($lat !== '' && $lng !== '') {
                $location = Location::firstOrCreate(
                    ['latitude' => (float) $lat, 'longitude' => (float) $lng],
                    ['label' => $label, 'id' => (string) Str::uuid()]
                );
                $location->images()->syncWithoutDetaching($image->id);
            }

            // Technique (every legacy image is "Imagem digital")
            $image->techniques()->syncWithoutDetaching($this->digitalImageTechnique->id);
        });
    }

    private function lookupUserUuid(string $legacyId): ?string
    {
        if ($legacyId === '' || $legacyId === '0') {
            return null;
        }
        if (array_key_exists($legacyId, $this->userIdCache)) {
            return $this->userIdCache[$legacyId];
        }
        $uuid = User::where('legacy_id', $legacyId)->value('id');
        return $this->userIdCache[$legacyId] = $uuid;
    }

    /**
     * Resolve a legacy `institution_id` to a local collective UUID, but only if
     * that collective actually exists locally. Falls back to null on a fresh
     * environment where CollectiveSeeder has not been run yet.
     */
    private function resolveCollectiveId(string $institutionId): ?string
    {
        $uuid = self::COLLECTIVE_MAP[$institutionId] ?? null;
        if ($uuid === null) {
            return null;
        }
        return isset($this->existingCollectiveIds[$uuid]) ? $uuid : null;
    }

    // ---------------------------------------------------------------------
    // Tags / authors / dates / license
    // ---------------------------------------------------------------------

    /**
     * Tags pipeline (ops 0–3, 50–52, 280, 288, 289).
     */
    private function processTags(?string $raw): string
    {
        $raw = (string) $raw;
        if ($raw === '' || strtolower($raw) === 'null') {
            return '';
        }
        $tokens = preg_split('/,\s*/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_map(fn ($t) => $this->titleCase(trim($t)), $tokens);
        $tokens = array_map(fn ($t) => $this->applyMap($t, $this->tagsMap), $tokens);
        $tokens = array_map(fn ($t) => mb_strtolower($t, 'UTF-8'), $tokens);
        $tokens = array_values(array_unique(array_filter($tokens, fn ($t) => $t !== '')));
        return implode(', ', $tokens);
    }

    /**
     * Authors pipeline (ops 36–49, 60).
     */
    private function processAuthors(?string $raw): string
    {
        $raw = (string) $raw;
        if ($raw === '' || strtolower($raw) === 'null') {
            return '';
        }
        $value = $this->applyMap($raw, $this->authorsMap);
        $tokens = preg_split('/;\s*/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_map(fn ($t) => $this->applyMap(trim($t), $this->authorsMap), $tokens);
        $tokens = array_map(function ($t) {
            if (str_contains($t, ',')) {
                $parts = array_map(fn ($p) => $this->titleCase(trim($p)), explode(',', $t));
                return implode(' ', array_reverse($parts));
            }
            return $t;
        }, $tokens);
        return implode(', ', array_filter($tokens, fn ($t) => $t !== ''));
    }

    /**
     * Date pipeline (ops 4–33, 53–58).
     * Returns [earliest, latest, circa] as strings.
     */
    private function processDates(?string $raw): array
    {
        $raw = (string) $raw;
        $earliest = $this->parseEarliest($raw);
        $latest = $this->parseLatest($raw);
        $circa = $this->parseCirca($raw);

        if ($latest !== '' && $this->shouldExpandToEndOfYear($raw, $circa)) {
            $latest = substr($latest, 0, 4).'-12-31';
        }

        if ($raw === '113') {
            $circa = '';
        }

        return [$earliest, $latest, $circa];
    }

    private function parseEarliest(string $raw): string
    {
        $v = trim($raw);
        if ($v === '' || $v === 'NULL' || $v === '113') return '';
        if ($v === '199700') return '1997-01-01';
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $v, $m)) return substr($m[0], 0, 10);
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $v, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('#^\d{4}#', $v, $m)) return $m[0].'-01-01';
        if (preg_match('#^\d{3}#', $v, $m)) return $m[0].'0-01-01';
        if ($v === 'XX') return '1900-01-01';
        if ($v === 'XXI') return '2000-01-01';
        return '';
    }

    private function parseLatest(string $raw): string
    {
        $v = trim($raw);
        if ($v === '' || $v === 'NULL' || $v === '113') return '';
        if ($v === '199700') return '1997-01-01';
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $v, $m)) return substr($m[0], 0, 10);
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $v, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('#^(\d{4})/(\d{2})$#', $v, $m)) {
            return substr($m[1], 0, 2).$m[2].'-01-01';
        }
        if (preg_match('#(\d{4})\s*$#', $v, $m)) return $m[1].'-01-01';
        if (preg_match('#(\d{3})\s*$#', $v, $m)) return $m[1].'0-01-01';
        if ($v === 'XX') return '1999-01-01';
        if ($v === 'XXI') return '2025-01-01';
        return '';
    }

    /**
     * VRA_DateCirca rules (ops 25–33):
     *   - 'a' or \d{3}/ or XX/XXI or bare \d{3} → true
     *   - contains '-' (ISO) → false
     *   - NULL → '' ; everything else → false
     */
    private function parseCirca(string $raw): string
    {
        $v = trim($raw);
        if ($v === '' || $v === 'NULL' || $v === '113') return '';
        if (str_contains($v, 'a')) return 'true';
        if (preg_match('#\d{3}/#', $v)) return 'true';
        if ($v === 'XX' || $v === 'XXI') return 'true';
        if (preg_match('/^\d{3}$/', $v)) return 'true';
        if (str_contains($v, '-')) return 'false';
        return 'false';
    }

    /** Ops 53–57: expand VRA_LatestDate to YYYY-12-31 for ranges/centuries. */
    private function shouldExpandToEndOfYear(string $raw, string $circa): bool
    {
        $v = trim($raw);
        if (preg_match('/^\d{4}$/', $v) && $circa === 'false') return true;
        if (preg_match('/a/i', $v)) return true;
        if (str_contains($v, '/') && $circa === 'true') return true;
        if (($v === 'XX' || $v === 'XXI') && $circa === 'true') return true;
        return false;
    }

    /** Ops 63–69: derive a Creative Commons license from the two boolean-ish columns. */
    private function licenseFor(string $commercial, string $modifications): string
    {
        $c = strtoupper(trim($commercial));
        $m = strtoupper(trim($modifications));
        $modAllowed = in_array($m, ['YES', 'YES_SA'], true);
        $modNo = $m === 'NO';

        if ($c === 'NO'  && $modAllowed) return 'CC BY-NC-SA';
        if ($c === 'NO'  && $modNo)      return 'CC BY-NC-ND';
        if ($c === 'YES' && $modAllowed) return 'CC BY-SA';
        if ($c === 'YES' && $modNo)      return 'CC BY-ND';
        return '';
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function applyMap(string $value, array $map): string
    {
        return $map[$value] ?? $value;
    }

    private function joinNonEmpty(array $parts, string $sep): string
    {
        $clean = array_filter(array_map(fn ($p) => (string) ($p ?? ''), $parts), fn ($p) => $p !== '');
        return implode($sep, $clean);
    }

    private function titleCase(string $s): string
    {
        return mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
    }

    private function parseTimestamp(?string $value): ?\Carbon\Carbon
    {
        if (empty($value)) return null;
        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Convenience CSV column order. */
    private function csvColumns(): array
    {
        return [
            'id', 'VRA_Right',
            'VRA_EarliestDate', 'VRA_LatestDate', 'VRA_DateCirca',
            'VRA_Title', 'VRA_Description', 'VRA_ImageContributor', 'VRA_Location_Refid',
            'nome_arquivo', 'street', 'district', 'city', 'state', 'country', 'complete_address',
            'latitude', 'longitude',
            'user_id', 'institution_id',
            'created_at', 'updated_at', 'deleted_at', 'workdate',
            'tags',
        ];
    }
}
