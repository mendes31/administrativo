<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\TrainingUsersRepository;

/**
 * Serviço responsável por atualizar os status dinâmicos de treinamentos
 * de forma controlada, evitando recalcular em toda requisição.
 *
 * Estratégia:
 * - Persiste no disco a última data/hora de execução em:
 *   storage/cache/system/training_status_last_run.json
 * - Apenas executa o update se:
 *   - Nunca foi executado, ou
 *   - Já passou mais que $minIntervalSeconds desde a última execução
 *
 * Pode ser chamado em:
 * - Login (após autenticação)
 * - Dashboard / list-training-status
 * - Scripts em cron (com parâmetro $force = true)
 */
class TrainingStatusUpdaterService
{
    private const DEFAULT_INTERVAL_SECONDS = 900; // 15 minutos

    /**
     * Garante que os status dinâmicos estejam atualizados,
     * respeitando o intervalo mínimo entre execuções.
     *
     * @param bool $force Se true, força atualização ignorando o intervalo.
     * @param int|null $minIntervalSeconds Intervalo mínimo em segundos (opcional).
     * @return void
     */
    public static function ensureUpdated(bool $force = false, ?int $minIntervalSeconds = null): void
    {
        try {
            $projectRoot = dirname(__DIR__, 4); // app/adms/Models/Services -> raiz
            $cacheDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0775, true);
            }

            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'training_status_last_run.json';
            $now = time();
            $interval = $minIntervalSeconds ?? self::DEFAULT_INTERVAL_SECONDS;

            if (!$force && file_exists($cacheFile)) {
                $content = file_get_contents($cacheFile);
                if ($content !== false) {
                    $data = json_decode($content, true);
                    if (is_array($data) && isset($data['last_run'])) {
                        $lastRun = (int)$data['last_run'];
                        // Se ainda está dentro do intervalo, não atualiza
                        if (($now - $lastRun) < $interval) {
                            return;
                        }
                    }
                }
            }

            // Executar atualização dos status dinâmicos
            $repo = new TrainingUsersRepository();
            $updated = $repo->updateDynamicStatuses();

            // Persistir nova data/hora de execução
            $payload = [
                'last_run' => $now,
                'updated'  => $updated,
                'datetime' => date('Y-m-d H:i:s', $now),
            ];
            file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // Não quebrar a requisição se algo der errado, apenas logar
            error_log('TrainingStatusUpdaterService::ensureUpdated error: ' . $e->getMessage());
        }
    }
}


