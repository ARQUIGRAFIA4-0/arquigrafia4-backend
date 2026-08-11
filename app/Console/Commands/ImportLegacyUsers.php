<?php

namespace App\Console\Commands;

use App\Models\Profile;
use App\Models\User;
use App\Models\VRACore\VRACContributorName;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportLegacyUsers extends Command
{
    protected $signature = 'users:import-legacy {file : Caminho para o arquivo CSV} {--verified-only : Importar apenas usuários com e-mail verificado (active=yes)}';

    protected $description = 'Importa usuários do sistema legado a partir de um CSV';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("Arquivo não encontrado: {$file}");
            return Command::FAILURE;
        }

        $verifiedOnly = $this->option('verified-only');
        $this->info("Iniciando importação: {$file}" . ($verifiedOnly ? ' (apenas verificados)' : ''));

        $existingLegacyIds = User::whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->info('Usuários já migrados: ' . count($existingLegacyIds));
        $this->newLine();

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);

        $created = $skipped = $failed = 0;

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($values = fgetcsv($handle)) !== false) {
            $row = array_combine($headers, $values);

            $legacyId = (int) $row['id'];
            $email    = trim((string) ($row['email'] ?? ''));

            if ($email === '' || strtoupper($email) === 'NULL') {
                $skipped++;
                $bar->advance();
                continue;
            }

            if ($verifiedOnly && strtolower((string) ($row['active'] ?? '')) !== 'yes') {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (isset($existingLegacyIds[$legacyId])) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (User::where('email', $email)->exists()) {
                $skipped++;
                $bar->advance();
                continue;
            }

            try {
                DB::transaction(function () use ($row, $legacyId) {
                    $first    = trim((string) ($row['name'] ?? ''));
                    $last     = $this->nullable($row['last_name'] ?? null);
                    $fullName = Str::ucwords(Str::lower(trim($first . ($last ? ' ' . $last : ''))));
                    $userId   = $row['uuid'] ?: (string) Str::uuid();

                    $user                   = new User();
                    $user->id               = $userId;
                    $user->name             = Str::limit($fullName, 60, '');
                    $user->email            = trim((string) $row['email']);
                    $user->password         = ! empty($row['password'])
                        ? (string) $row['password']
                        : Hash::make('UmaSenhaSimples');
                    $user->legacy_id        = $legacyId;
                    $user->email_verified_at = strtolower((string) ($row['active'] ?? '')) === 'yes' ? now() : null;
                    if (! empty($row['created_at'])) {
                        $user->created_at = $row['created_at'];
                    }
                    if (! empty($row['updated_at'])) {
                        $user->updated_at = $row['updated_at'];
                    }
                    $user->save();

                    $profile                = new Profile();
                    $profile->user_id       = $userId;
                    $configurations         = [];

                    if ($gender = $this->nullable($row['gender'] ?? null)) {
                        $profile->gender              = Str::substr($gender, 0, 20);
                        $configurations['gender']     = false;
                    }
                    if ($birthday = $this->nullable($row['birthday'] ?? null)) {
                        $profile->birthdate           = $birthday;
                        $configurations['birthdate']  = false;
                    }
                    if ($scholarity = $this->nullable($row['scholarity'] ?? null)) {
                        $profile->scholarity          = Str::substr($scholarity, 0, 20);
                        $configurations['scholarity'] = false;
                    }
                    $city    = $this->nullable($row['city'] ?? null);
                    $state   = $this->nullable($row['state'] ?? null);
                    $country = $this->nullable($row['country'] ?? null);
                    if ($city || $state || $country) {
                        $profile->address           = Str::substr(trim("{$city} {$state} {$country}"), 0, 255);
                        $configurations['address']  = false;
                    }
                    $profile->configurations = $configurations;
                    $profile->save();

                    $contributor              = new VRACContributorName();
                    $contributor->name        = $fullName;
                    $contributor->type        = 'personal';
                    $contributor->vocab       = 'ARQUIGRAFIA';
                    $contributor->ref_id      = $userId;
                    $contributor->user_id     = $userId;
                    $contributor->save();
                });

                $existingLegacyIds[$legacyId] = true;
                $created++;
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->warn("Falhou legacy_id={$legacyId}: " . $e->getMessage());
            }

            $bar->advance();
        }

        fclose($handle);
        $bar->finish();
        $this->newLine(2);

        $this->info('══════════════════════════════════════');
        $this->info("Criados  : {$created}");
        $this->warn("Saltados : {$skipped}");
        $this->warn("Falhos   : {$failed}");
        $this->info('══════════════════════════════════════');

        return Command::SUCCESS;
    }

    private function nullable(mixed $value): ?string
    {
        if ($value === null) return null;
        $v = trim((string) $value);
        if ($v === '' || strtoupper($v) === 'NULL') return null;
        return $v;
    }
}
