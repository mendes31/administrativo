<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Rotinas SST disparadas no login (máx. 1× a cada 24 h), no mesmo padrão de
 * treinamentos e retenção LGPD — o ambiente Windows da Tiaraju não depende
 * só de Task Scheduler para o piloto.
 */
final class SstMaintenanceService
{
    private const DEFAULT_INTERVAL_SECONDS = 86400;

    private const CACHE_FILENAME = 'sst_maintenance_last_run.json';

    public static function ensureUpdated(bool $force = false, ?int $minIntervalSeconds = null): void
    {
        try {
            $cacheFile = self::cacheFilePath();
            $now = time();
            $interval = $minIntervalSeconds ?? self::DEFAULT_INTERVAL_SECONDS;

            if (!$force && is_file($cacheFile)) {
                $content = file_get_contents($cacheFile);
                if ($content !== false) {
                    $data = json_decode($content, true);
                    if (is_array($data) && isset($data['last_run'])) {
                        $lastRun = (int) $data['last_run'];
                        if (($now - $lastRun) < $interval) {
                            return;
                        }
                    }
                }
            }

            $vistoria = ['created' => 0, 'skipped' => 0, 'overdue_marked' => 0, 'errors' => []];
            try {
                $vistoria = (new SstEquipamentoVistoriaGeneratorService())->run();
            } catch (\Throwable $e) {
                error_log('SstMaintenanceService vistoria: ' . $e->getMessage());
                $vistoria['errors'][] = $e->getMessage();
            }

            $digest = ['sent' => 0, 'skipped' => 0, 'failed' => 0, 'disabled' => true];
            try {
                $digest = (new SstNotificationService())->sendPendenciasDigest();
            } catch (\Throwable $e) {
                error_log('SstMaintenanceService pendencias: ' . $e->getMessage());
                $digest['failed'] = 1;
                $digest['errors'] = [$e->getMessage()];
            }

            self::writeCache($now, $vistoria, $digest);
        } catch (\Throwable $e) {
            error_log('SstMaintenanceService::ensureUpdated error: ' . $e->getMessage());
        }
    }

    /**
     * @return array{last_run:?int, datetime:?string}|null
     */
    public static function lastRunMeta(): ?array
    {
        $file = self::cacheFilePath();
        if (!is_file($file)) {
            return null;
        }
        $raw = file_get_contents($file);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['last_run'])) {
            return null;
        }

        return [
            'last_run' => (int) $data['last_run'],
            'datetime' => (string) ($data['datetime'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $vistoria
     * @param array<string, mixed> $digest
     */
    private static function writeCache(int $now, array $vistoria, array $digest): void
    {
        $dir = dirname(self::cacheFilePath());
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(self::cacheFilePath(), json_encode([
            'last_run' => $now,
            'datetime' => date('Y-m-d H:i:s', $now),
            'vistoria' => [
                'created' => (int) ($vistoria['created'] ?? 0),
                'skipped' => (int) ($vistoria['skipped'] ?? 0),
                'overdue_marked' => (int) ($vistoria['overdue_marked'] ?? 0),
            ],
            'digest' => [
                'sent' => (int) ($digest['sent'] ?? 0),
                'skipped' => (int) ($digest['skipped'] ?? 0),
                'failed' => (int) ($digest['failed'] ?? 0),
                'disabled' => !empty($digest['disabled']),
            ],
        ], JSON_UNESCAPED_UNICODE));
    }

    private static function cacheFilePath(): string
    {
        $root = dirname(__DIR__, 4);

        return $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR
            . 'cache' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR
            . self::CACHE_FILENAME;
    }
}
