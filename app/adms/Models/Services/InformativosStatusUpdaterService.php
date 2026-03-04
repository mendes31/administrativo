<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\InformativosRepository;

/**
 * Serviço responsável por aplicar, de forma controlada,
 * a ativação/inativação de informativos com base em publish_at/expire_at.
 *
 * Estratégia semelhante a outros serviços do sistema:
 * - Persiste em disco a última execução em:
 *   storage/cache/system/informativos_status_last_run.json
 * - Só roda novamente após um intervalo mínimo ou quando forçado.
 */
class InformativosStatusUpdaterService
{
    private const DEFAULT_INTERVAL_SECONDS = 600; // 10 minutos

    /**
     * Garante que o status dos informativos esteja atualizado
     * (ativa os já publicados, inativa os expirados),
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

            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'informativos_status_last_run.json';
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

            $repo = new InformativosRepository();
            $result = $repo->updateActiveFromSchedule();

            $payload = [
                'last_run' => $now,
                'datetime' => date('Y-m-d H:i:s', $now),
                'updated'  => $result,
            ];

            file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // Não quebrar a requisição se algo der errado, apenas logar
            error_log('InformativosStatusUpdaterService::ensureUpdated error: ' . $e->getMessage());
        }
    }
}

