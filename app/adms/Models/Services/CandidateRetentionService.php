<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\RhCandidatosRepository;

/**
 * Serviço responsável por aplicar, de forma controlada,
 * as políticas de retenção/anonimização de currículos (RH).
 *
 * Estratégia semelhante ao TrainingStatusUpdaterService:
 * - Persiste em disco a última execução em:
 *   storage/cache/system/candidate_retention_last_run.json
 * - Só roda novamente após um intervalo mínimo ou quando forçado.
 */
class CandidateRetentionService
{
    private const DEFAULT_INTERVAL_SECONDS = 86400; // 24 horas

    /**
     * Garante que a política de retenção de currículos seja aplicada,
     * respeitando o intervalo mínimo entre execuções.
     *
     * @param bool $force Se true, força execução ignorando o intervalo.
     * @param int|null $minIntervalSeconds Intervalo mínimo em segundos (opcional).
     */
    public static function ensureUpdated(bool $force = false, ?int $minIntervalSeconds = null): void
    {
        try {
            $projectRoot = dirname(__DIR__, 4); // app/adms/Models/Services -> raiz
            $cacheDir = $projectRoot
                . DIRECTORY_SEPARATOR . 'storage'
                . DIRECTORY_SEPARATOR . 'cache'
                . DIRECTORY_SEPARATOR . 'system';

            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0775, true);
            }

            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'candidate_retention_last_run.json';
            $now = time();
            $interval = $minIntervalSeconds ?? self::DEFAULT_INTERVAL_SECONDS;

            if (!$force && file_exists($cacheFile)) {
                $content = file_get_contents($cacheFile);
                if ($content !== false) {
                    $data = json_decode($content, true);
                    if (is_array($data) && isset($data['last_run'])) {
                        $lastRun = (int) $data['last_run'];
                        if (($now - $lastRun) < $interval) {
                            // Ainda dentro do intervalo seguro: não executa de novo.
                            return;
                        }
                    }
                }
            }

            $repo = new RhCandidatosRepository();
            $result = $repo->aplicarPoliticaRetencao();

            $payload = [
                'last_run' => $now,
                'datetime' => date('Y-m-d H:i:s', $now),
                'result'   => $result,
            ];

            file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // Não quebrar a requisição se algo der errado, apenas logar
            error_log('CandidateRetentionService::ensureUpdated error: ' . $e->getMessage());
        }
    }
}


