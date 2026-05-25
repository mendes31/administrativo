<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Limpeza periódica de inscrições push expiradas (410/404).
 * Roda no máximo 1x a cada 6 horas, integrado aos serviços diários.
 */
final class PushSubscriptionMaintenanceService
{
    private const MIN_INTERVAL_SECONDS = 21600; // 6 h

    public static function ensureUpdated(bool $force = false): void
    {
        try {
            $projectRoot = dirname(__DIR__, 4);
            $cacheDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage'
                . DIRECTORY_SEPARATOR . 'cache'
                . DIRECTORY_SEPARATOR . 'system';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0775, true);
            }

            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'push_prune_last_run.json';

            if (!$force && is_file($cacheFile)) {
                $data = json_decode((string) file_get_contents($cacheFile), true);
                $lastRun = (int) ($data['last_run'] ?? 0);
                if ($lastRun > 0 && (time() - $lastRun) < self::MIN_INTERVAL_SECONDS) {
                    return;
                }
            }

            $service = new PushNotificationService();
            $summary = $service->pruneExpiredSubscriptions(300);

            file_put_contents($cacheFile, json_encode([
                'last_run' => time(),
                'checked' => (int) ($summary['checked'] ?? 0),
                'removed' => (int) ($summary['removed'] ?? 0),
                'failed' => (int) ($summary['failed'] ?? 0),
            ], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            error_log('PushSubscriptionMaintenanceService: ' . $e->getMessage());
        }
    }
}
