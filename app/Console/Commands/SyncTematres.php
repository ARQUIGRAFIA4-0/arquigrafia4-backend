<?php

namespace App\Console\Commands;

use App\Models\VRACore\VRACMaterial;
use App\Models\VRACore\VRACStylePeriod;
use App\Models\VRACore\VRACTechnique;
use App\Models\VRACore\VRACWorkType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncTematres extends Command
{
    protected $signature = 'sync:tematres {--limit=0 : Limit number of terms per category (0 = unlimited)}';

    protected $description = 'Fetch VCAA thesaurus terms and populate VRACore vocabulary tables';

    protected string $baseUrl = 'https://vocabularios.eca.usp.br/vcaa/services.php';

    protected array $rootMapping = [
        '3'    => ['model' => VRACWorkType::class,    'extra' => []],
        '4'    => ['model' => VRACTechnique::class,   'extra' => []],
        '16'   => ['model' => VRACMaterial::class,    'extra' => ['type' => 'medium']],
        '1870' => ['model' => VRACStylePeriod::class, 'extra' => []],
    ];

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $total = 0;

        foreach ($this->rootMapping as $rootId => $config) {
            $modelClass = $config['model'];
            $shortName  = class_basename($modelClass);

            $this->info("Syncing {$shortName} (root ID: {$rootId})...");
            $count = $this->syncBranch($rootId, $modelClass, $config['extra'], $limit);
            $this->info("  → {$count} terms processed.");
            $total += $count;
        }

        $this->info("Done. Total: {$total} terms processed.");
        return 0;
    }

    protected function syncBranch(string $rootId, string $modelClass, array $extra, int $limit): int
    {
        $queue    = [$rootId];
        $imported = 0;
        $seen     = [];

        while (!empty($queue)) {
            $current = array_shift($queue);

            if (isset($seen[$current])) {
                continue;
            }
            $seen[$current] = true;

            $resp = $this->callApi(['task' => 'fetchDown', 'arg' => $current, 'output' => 'json']);
            if (!$resp || !isset($resp['result'])) {
                $this->warn("  No results for ID {$current}");
                continue;
            }

            foreach ($resp['result'] as $key => $item) {
                $vcaaId   = (string) ($item['term_id'] ?? ($item['id'] ?? $key));
                $termText = $item['string'] ?? null;

                if (!$vcaaId || !$termText) {
                    continue;
                }

                $modelClass::updateOrCreate(
                    ['ref_id' => $vcaaId],
                    array_merge(['label' => $termText, 'vocab' => 'VCAA'], $extra)
                );

                $imported++;

                if ($limit > 0 && $imported >= $limit) {
                    $this->info("  Limit of {$limit} reached.");
                    return $imported;
                }

                if (!empty($item['hasMoreDown'])) {
                    $queue[] = $vcaaId;
                }

                usleep(100000); // 0.1s — be polite to the remote server
            }
        }

        return $imported;
    }

    protected function callApi(array $params): ?array
    {
        try {
            $response = Http::timeout(60)->get($this->baseUrl, $params);
            if (!$response->successful()) {
                $this->warn('API returned: ' . $response->status());
                return null;
            }
            return $response->json();
        } catch (\Exception $e) {
            $this->warn('HTTP error: ' . $e->getMessage());
            return null;
        }
    }
}
