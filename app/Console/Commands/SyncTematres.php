<?php

namespace App\Console\Commands;

use App\Models\VRACore\VRACAgentRole;
use App\Models\VRACore\VRACMaterial;
use App\Models\VRACore\VRACStylePeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\VRACore\VRACSubject;
use App\Models\VRACore\VRACTechnique;
use App\Models\VRACore\VRACWorkType;

class SyncTematres extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:tematres {--limit=0 : optional limit number of terms to import (0 = unlimited)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch VCAA thesaurus terms (Tematres) and populate the vocabularies table';

    protected string $baseUrl = 'https://vocabularios.eca.usp.br/vcaa/services.php';

    public function handle(): int
    {
        $this->info('Starting Tematres sync...');

        $limit = (int) $this->option('limit');
        $imported = 0;

        // $queue = [];
        $queue = ['3794'];

        // fetch top-level terms
        // $resp = $this->callApi(['task' => 'fetchTopTerms', 'output' => 'json']);
        // if (! $resp || ! isset($resp['result'])) {
        //     $this->error('Failed to fetch top terms or unexpected response');
        //     return 1;
        // }

        // foreach ($resp['result'] as $key => $obj) {
        //     // each obj has term_id property and key is same id
        //     $termId = $obj['term_id'] ?? ($obj['id'] ?? $key);
        //     if (!$termId) {
        //         continue;
        //     }
        //     $queue[] = $termId;
        // }

        // BFS/queue processing
        while (!empty($queue)) {
            $current = array_shift($queue);

            $this->info("Fetching down for id: {$current}");
            $resp = $this->callApi(['task' => 'fetchDown', 'arg' => $current, 'output' => 'json']);
            if (! $resp || ! isset($resp['result'])) {
                $this->warn("No results for id {$current}");
                continue;
            }

            foreach ($resp['result'] as $key => $item) {
                $vcaaId = $item['term_id'] ?? ($item['id'] ?? $key);
                $termText = $item['string'] ?? null;
                $hasMoreDown = isset($item['hasMoreDown']) ? (int) $item['hasMoreDown'] : 0;

                if (!$vcaaId || !$termText) {
                    continue;
                }

                // Upsert the vocabulary term
                // VRACSubject::updateOrCreate(
                    // ['ref_id' => $vcaaId,],
                VRACAgentRole::create([
                    'label' => $termText,
                    // 'type' => 'medium',
                    'ref_id' => $vcaaId,
                    'vocab' => 'VCAA',
                    // 'source' => 'string', URL for the specific vcaaId
                ]);

                $imported++;
                if ($limit > 0 && $imported >= $limit) {
                    $this->info("Reached limit of {$limit} imports. Stopping.");
                    return 0;
                }

                if ($hasMoreDown > 0) {
                    // push the term_id to queue for further expansion
                    $queue[] = $vcaaId;
                }

                // be polite to remote server
                usleep(100000); // 0.1s
            }
        }

        $this->info("Imported/updated {$imported} terms.");
        return 0;
    }

    protected function callApi(array $params)
    {
        try {
            $response = Http::timeout(60)->get($this->baseUrl, $params);
            if (!$response->successful()) {
                $this->warn('Remote API returned non-success: ' . $response->status());
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->warn('HTTP request failed: ' . $e->getMessage());
            return null;
        }
    }
}

                // VRACSubject::create([
                //     'term' => $termText,
                //     'type' => 'otherTopic',
                //     'ref_id' => $vcaaId,
                //     'vocab' => 'VCAA',
                //     // 'source' => 'string', URL for the specific vcaaId
                // ]);

                // ID inicial = 3
                // VRACWorkType::create([
                //     'label' => $termText,
                //     'ref_id' => $vcaaId,
                //     'vocab' => 'VCAA',
                // ]);

                // ID inicial = 4
                // VRACTechnique::create([
                //     'label' => $termText,
                //     'ref_id' => $vcaaId,
                //     'vocab' => 'VCAA',
                // ]);

                // ID inicial = 1870
                // VRACStylePeriod::create([
                //     'label' => $termText,
                //     'ref_id' => $vcaaId,
                //     'vocab' => 'VCAA',
                // ]);

                // ID inicial = 16
                // VRACMaterial::create([
                //     'label' => $termText,
                //     'type' => 'medium',
                //     'ref_id' => $vcaaId,
                //     'vocab' => 'VCAA',
                // ]);

                // ID inicial = 3794
                // VRACAgentRole::create([
                //     'label' => $termText,
                //     'ref_id' => $vcaaId,
                //     'vocab' => 'VCAA',
                // ]);
