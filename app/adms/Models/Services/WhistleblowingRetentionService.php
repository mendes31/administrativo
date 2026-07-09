<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\WhistleblowingMessagesRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;

/**
 * Retenção LGPD: arquivar após N anos e excluir após M anos,
 * contados a partir da data de encerramento (`closed_at`) da denúncia.
 *
 * Disparo automático: {@see self::ensureUpdated()} no login (máx. 1× a cada 24 h).
 */
final class WhistleblowingRetentionService
{
    private const DEFAULT_INTERVAL_SECONDS = 86400; // 24 horas

    private const CACHE_FILENAME = 'whistleblowing_retention_last_run.json';

    /**
     * Garante execução da retenção no primeiro acesso do dia (login),
     * respeitando intervalo mínimo e flag «Retenção automática ativa» na config.
     */
    public static function ensureUpdated(bool $force = false, ?int $minIntervalSeconds = null): void
    {
        try {
            $configRepo = new \App\adms\Models\Repository\WhistleblowingConfigRepository();
            if (!$configRepo->isCronEnabled() && !$force) {
                return;
            }

            $cacheFile = self::cacheFilePath();
            $now = time();
            $interval = $minIntervalSeconds ?? self::DEFAULT_INTERVAL_SECONDS;

            if (!$force && file_exists($cacheFile)) {
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

            $result = (new self())->run('auto');
            self::writeCache($now, $result);
        } catch (\Throwable $e) {
            error_log('WhistleblowingRetentionService::ensureUpdated error: ' . $e->getMessage());
        }
    }

    public function run(string $triggeredBy = 'cron'): array
    {
        $startedAt = microtime(true);
        $reportsRepo = new WhistleblowingReportsRepository();
        $messagesRepo = new WhistleblowingMessagesRepository();
        $conn = $reportsRepo->getConnection();

        $runId = $this->startRun($conn, $triggeredBy);
        $archived = 0;
        $deleted = 0;
        $attachmentsDeleted = 0;
        $errorMessage = null;

        try {
            $archived = $reportsRepo->archiveExpiredReports();

            foreach ($reportsRepo->getReportIdsDueForDeletion() as $reportId) {
                $attachments = $messagesRepo->getAttachmentsByReportId($reportId);
                foreach ($attachments as $att) {
                    if (WhistleblowingUploadService::deleteFile((string) ($att['stored_name'] ?? ''))) {
                        $attachmentsDeleted++;
                    }
                }
                if ($reportsRepo->deleteReportById($reportId)) {
                    $deleted++;
                }
            }
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $status = $errorMessage === null ? 'success' : 'error';

        if ($runId !== null) {
            $this->finishRun($conn, $runId, $status, $archived, $deleted, $attachmentsDeleted, $durationMs, $errorMessage);
        }

        if ($errorMessage !== null) {
            throw new \RuntimeException($errorMessage);
        }

        if (!in_array($triggeredBy, ['auto'], true)) {
            self::writeCache(time(), [
                'archived' => $archived,
                'deleted' => $deleted,
                'attachments_deleted' => $attachmentsDeleted,
                'duration_ms' => $durationMs,
            ]);
        }

        return [
            'archived' => $archived,
            'deleted' => $deleted,
            'attachments_deleted' => $attachmentsDeleted,
            'duration_ms' => $durationMs,
        ];
    }

    private function startRun(\PDO $conn, string $triggeredBy): ?int
    {
        try {
            $stmt = $conn->prepare(
                'INSERT INTO adms_whistleblowing_retention_runs
                 (started_at, status, triggered_by)
                 VALUES (NOW(), :status, :triggered_by)'
            );
            $stmt->execute([
                ':status' => 'running',
                ':triggered_by' => in_array($triggeredBy, ['cron', 'manual', 'auto'], true) ? $triggeredBy : 'cron',
            ]);

            return (int) $conn->lastInsertId();
        } catch (\Throwable) {
            return null;
        }
    }

    private function finishRun(
        \PDO $conn,
        int $runId,
        string $status,
        int $archived,
        int $deleted,
        int $attachmentsDeleted,
        int $durationMs,
        ?string $message
    ): void {
        try {
            $stmt = $conn->prepare(
                'UPDATE adms_whistleblowing_retention_runs
                 SET finished_at = NOW(),
                     status = :status,
                     archived_count = :archived,
                     deleted_count = :deleted,
                     attachments_deleted = :attachments,
                     duration_ms = :duration_ms,
                     message = :message
                 WHERE id = :id'
            );
            $stmt->execute([
                ':status' => $status,
                ':archived' => $archived,
                ':deleted' => $deleted,
                ':attachments' => $attachmentsDeleted,
                ':duration_ms' => $durationMs,
                ':message' => $message,
                ':id' => $runId,
            ]);
        } catch (\Throwable) {
            // Auditoria não deve interromper o fluxo.
        }
    }

    private static function cacheFilePath(): string
    {
        $projectRoot = dirname(__DIR__, 4);
        $cacheDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }

        return $cacheDir . DIRECTORY_SEPARATOR . self::CACHE_FILENAME;
    }

    /** @param array<string, mixed> $result */
    private static function writeCache(int $timestamp, array $result): void
    {
        $payload = [
            'last_run' => $timestamp,
            'datetime' => date('Y-m-d H:i:s', $timestamp),
            'result' => $result,
        ];
        file_put_contents(self::cacheFilePath(), json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
