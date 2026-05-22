<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Repository\PoliciesRepository;

/**
 * Serviço responsável por aplicar, de forma controlada,
 * a ativação/inativação de informativos com base em publish_at/expire_at.
 *
 * Estratégia:
 * - Persiste a última execução em storage/cache/system/informativos_status_last_run.json
 * - Intervalo mínimo entre execuções (padrão 60s), compartilhado por dashboard, listagem e visualização
 * - flock não bloqueante no lock file: em picos de acesso, só um processo executa os UPDATEs;
 *   os demais saem imediatamente sem consultar o banco
 */
class InformativosStatusUpdaterService
{
    /** Intervalo global recomendado (dashboard, lista, view compartilham o mesmo last_run). */
    public const STANDARD_THROTTLE_SECONDS = 60;

    private const DEFAULT_INTERVAL_SECONDS = self::STANDARD_THROTTLE_SECONDS;

    /**
     * Garante que o status dos informativos esteja atualizado
     * (ativa os já publicados, inativa os expirados),
     * respeitando o intervalo mínimo entre execuções e evitando corrida entre muitos usuários.
     *
     * @param bool $force Se true, ignora o intervalo (ainda usa lock não bloqueante; se não obtiver lock, retorna).
     * @param int|null $minIntervalSeconds Intervalo mínimo em segundos; null usa o padrão. Zero ou negativo vira o padrão.
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
            $lockFile = $cacheDir . DIRECTORY_SEPARATOR . 'informativos_status_updater.lock';

            $rawInterval = $minIntervalSeconds ?? self::DEFAULT_INTERVAL_SECONDS;
            $interval = $rawInterval > 0 ? $rawInterval : self::DEFAULT_INTERVAL_SECONDS;
            $now = time();

            if (!$force && self::isWithinInterval($cacheFile, $now, $interval)) {
                return;
            }

            $lockHandle = fopen($lockFile, 'c+');
            if ($lockHandle === false) {
                return;
            }

            if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
                fclose($lockHandle);
                return;
            }

            try {
                if (!$force && self::isWithinInterval($cacheFile, $now, $interval)) {
                    return;
                }

                $repo = new InformativosRepository();
                $result = $repo->updateActiveFromSchedule();

                foreach ($result['ativados_ids'] ?? [] as $informativoId) {
                    InformativoPublishNotifier::notifyPublished((int) $informativoId);
                }

                $policiesRepo = new PoliciesRepository();
                $policyResult = $policiesRepo->updateActiveFromSchedule();
                foreach ($policyResult['ativados_ids'] ?? [] as $policyId) {
                    PolicyPublishNotifier::notifyPublished((int) $policyId);
                }

                $payload = [
                    'last_run' => $now,
                    'datetime' => date('Y-m-d H:i:s', $now),
                    'updated'  => $result,
                ];

                file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE));
            } finally {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }
        } catch (\Throwable $e) {
            error_log('InformativosStatusUpdaterService::ensureUpdated error: ' . $e->getMessage());
        }
    }

    private static function isWithinInterval(string $cacheFile, int $now, int $interval): bool
    {
        if ($interval <= 0 || !file_exists($cacheFile)) {
            return false;
        }
        $content = file_get_contents($cacheFile);
        if ($content === false) {
            return false;
        }
        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['last_run'])) {
            return false;
        }
        $lastRun = (int) $data['last_run'];

        return ($now - $lastRun) < $interval;
    }
}
