<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeLegacyAgents extends Command
{
    protected $signature = 'agents:merge-legacy
                            {--csv= : Caminho para o CSV com os pares (modo produção)}
                            {--auto : Descobrir pares automaticamente pelo nome (modo local)}
                            {--dry-run : Simula a execução sem alterar dados}';

    protected $description = 'Mescla contributor names legados duplicados com seus correspondentes ARQUIGRAFIA';

    private bool $dryRun          = false;
    private int  $pairsProcessed    = 0;
    private int  $pivotsMoved       = 0;
    private int  $pivotsDeleted     = 0;
    private int  $agentsDeleted     = 0;
    private int  $agentsRedirected  = 0;
    private int  $contribsDeleted   = 0;
    private int  $errors            = 0;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        if ($this->dryRun) {
            $this->warn('⚠  DRY-RUN ativo — nenhuma alteração será salva');
            $this->newLine();
        }

        if ($this->option('csv')) {
            $pairs = $this->loadFromCsv((string) $this->option('csv'));
        } elseif ($this->option('auto')) {
            $pairs = $this->discoverPairs();
        } else {
            $this->error('Informe --csv=<arquivo> ou --auto');
            return Command::FAILURE;
        }

        if (empty($pairs)) {
            $this->info('Nenhum par de duplicados encontrado.');
            return Command::SUCCESS;
        }

        $this->info('Pares a processar: ' . count($pairs));
        $bar = $this->output->createProgressBar(count($pairs));
        $bar->start();

        foreach ($pairs as $pair) {
            try {
                $this->mergePair($pair['legacy_id'], $pair['arquigrafia_id']);
                $this->pairsProcessed++;
            } catch (\Throwable $e) {
                $this->errors++;
                $this->newLine();
                $this->warn("Erro no par {$pair['legacy_id']}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->printSummary();

        return $this->errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    // ─── Carregamento de pares ────────────────────────────────────────────────

    private function loadFromCsv(string $file): array
    {
        if (! file_exists($file)) {
            $this->error("Arquivo não encontrado: {$file}");
            return [];
        }

        $handle  = fopen($file, 'r');
        $headers = fgetcsv($handle);
        $pairs   = [];

        while (($values = fgetcsv($handle)) !== false) {
            $row     = array_combine($headers, $values);
            $pairs[] = [
                'legacy_id'      => trim($row['legacy_contrib_id']),
                'arquigrafia_id' => trim($row['arquigrafia_contrib_id']),
            ];
        }

        fclose($handle);
        return $pairs;
    }

    private function discoverPairs(): array
    {
        // Só processa casos onde há exatamente 1 correspondente ARQUIGRAFIA com o mesmo nome.
        // Múltiplos matches indicam ambiguidade (ex: transform:legacy rodou mais de uma vez);
        // esses casos são pulados para evitar merge no destino errado.
        $rows = DB::select("
            SELECT   cn_old.id  AS legacy_id,
                     MIN(cn_new.id) AS arquigrafia_id
            FROM     vrac_contributor_names cn_old
            JOIN     vrac_contributor_names cn_new
                ON   cn_new.name    = cn_old.name
                AND  cn_new.vocab   = 'ARQUIGRAFIA'
                AND  cn_new.user_id IS NOT NULL
                AND  cn_new.id     != cn_old.id
            WHERE    (cn_old.vocab IS NULL OR cn_old.vocab != 'ARQUIGRAFIA')
            GROUP BY cn_old.id
            HAVING   COUNT(DISTINCT cn_new.id) = 1
        ");

        return array_map(fn ($r) => [
            'legacy_id'      => $r->legacy_id,
            'arquigrafia_id' => $r->arquigrafia_id,
        ], $rows);
    }

    // ─── Merge de um par ─────────────────────────────────────────────────────

    private function mergePair(string $legacyContribId, string $arquigrafiaContribId): void
    {
        if ($this->dryRun) {
            $this->simulateMerge($legacyContribId, $arquigrafiaContribId);
            return;
        }

        DB::transaction(function () use ($legacyContribId, $arquigrafiaContribId) {
            $this->executeMerge($legacyContribId, $arquigrafiaContribId);
        });
    }

    private function executeMerge(string $legacyContribId, string $arquigrafiaContribId): void
    {
        $legacyAgents      = $this->getAgents($legacyContribId);
        $arquigrafiaByRole = $this->getAgentsByRole($arquigrafiaContribId);

        foreach ($legacyAgents as $legacyAgent) {
            $legacyAgentId = $legacyAgent->id;
            $roleId        = $legacyAgent->role_id;

            if (isset($arquigrafiaByRole[$roleId])) {
                $targetAgentId = $arquigrafiaByRole[$roleId];

                // Move pivots que ainda não existem no agente destino.
                // O subselect duplo é necessário para o MySQL permitir UPDATE e SELECT
                // na mesma tabela sem erro "You can't specify target table".
                $moved = DB::update("
                    UPDATE agent_image
                    SET    agent_id = ?
                    WHERE  agent_id = ?
                    AND    image_id NOT IN (
                        SELECT image_id FROM (
                            SELECT image_id FROM agent_image WHERE agent_id = ?
                        ) AS _already
                    )
                ", [$targetAgentId, $legacyAgentId, $targetAgentId]);

                $this->pivotsMoved += $moved;

                // Remove pivots duplicados que já existiam no agente destino
                $deleted = DB::table('agent_image')->where('agent_id', $legacyAgentId)->delete();
                $this->pivotsDeleted += $deleted;

                // Remove datas associadas ao agente legado (sem FK, mas limpeza necessária)
                DB::table('vrac_agent_dates')->where('agent_id', $legacyAgentId)->delete();

                // Remove o agente legado (force delete, sem soft-delete)
                DB::table('vrac_agents')->where('id', $legacyAgentId)->delete();
                $this->agentsDeleted++;
            } else {
                // Papel não existe no contributor ARQUIGRAFIA — redireciona o agente
                DB::table('vrac_agents')
                    ->where('id', $legacyAgentId)
                    ->update(['contributor_name_id' => $arquigrafiaContribId]);
                $this->agentsRedirected++;
            }
        }

        // Redireciona agentes soft-deleted que ainda apontam para o contributor legado,
        // evitando violação de FK ao deletar o contributor_name.
        DB::table('vrac_agents')
            ->where('contributor_name_id', $legacyContribId)
            ->whereNotNull('deleted_at')
            ->update(['contributor_name_id' => $arquigrafiaContribId]);

        // Remove o contributor name legado
        DB::table('vrac_contributor_names')->where('id', $legacyContribId)->delete();
        $this->contribsDeleted++;
    }

    private function simulateMerge(string $legacyContribId, string $arquigrafiaContribId): void
    {
        $legacyAgents      = $this->getAgents($legacyContribId);
        $arquigrafiaByRole = $this->getAgentsByRole($arquigrafiaContribId);

        foreach ($legacyAgents as $legacyAgent) {
            $legacyAgentId = $legacyAgent->id;
            $roleId        = $legacyAgent->role_id;

            if (! isset($arquigrafiaByRole[$roleId])) {
                // Agente vai ser redirecionado (contributor_name_id atualizado)
                $this->agentsRedirected++;
                continue;
            }

            $targetAgentId = $arquigrafiaByRole[$roleId];

            $toMove = DB::selectOne("
                SELECT COUNT(*) AS cnt
                FROM   agent_image
                WHERE  agent_id = ?
                AND    image_id NOT IN (
                    SELECT image_id FROM agent_image WHERE agent_id = ?
                )
            ", [$legacyAgentId, $targetAgentId]);

            $toDel = DB::selectOne("
                SELECT COUNT(*) AS cnt
                FROM   agent_image
                WHERE  agent_id = ?
                AND    image_id IN (
                    SELECT image_id FROM agent_image WHERE agent_id = ?
                )
            ", [$legacyAgentId, $targetAgentId]);

            $this->pivotsMoved   += (int) $toMove->cnt;
            $this->pivotsDeleted += (int) $toDel->cnt;
            $this->agentsDeleted++;
        }

        $this->contribsDeleted++;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function getAgents(string $contributorNameId): array
    {
        return DB::select(
            'SELECT id, role_id FROM vrac_agents WHERE contributor_name_id = ? AND deleted_at IS NULL',
            [$contributorNameId]
        );
    }

    private function getAgentsByRole(string $contributorNameId): array
    {
        $agents = $this->getAgents($contributorNameId);
        $byRole = [];
        foreach ($agents as $a) {
            $byRole[$a->role_id] = $a->id;
        }
        return $byRole;
    }

    // ─── Relatório final ──────────────────────────────────────────────────────

    private function printSummary(): void
    {
        $tag = $this->dryRun ? ' (DRY-RUN)' : '';
        $this->info('══════════════════════════════════════════════');
        $this->info("Pares processados{$tag}  : {$this->pairsProcessed}");
        $this->info("Pivôs movidos           : {$this->pivotsMoved}");
        $this->info("Pivôs deletados         : {$this->pivotsDeleted}");
        $this->info("Agentes deletados       : {$this->agentsDeleted}");
        $this->info("Agentes redirecionados  : {$this->agentsRedirected}");
        $this->info("Contributors deletados  : {$this->contribsDeleted}");

        if ($this->errors > 0) {
            $this->warn("Erros                  : {$this->errors}");
        }

        $this->info('══════════════════════════════════════════════');
    }
}
