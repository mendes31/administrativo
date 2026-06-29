<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvInventorySapSyncRunsRepository extends DbConnection
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        if (!$this->tableExists()) {
            return 0;
        }
        $sql = 'INSERT INTO inv_inventory_sap_sync_runs (
                    sync_type, sync_mode, filter_from_date,
                    rows_created, rows_updated, rows_unchanged, rows_failed,
                    status, error_log, started_at, finished_at
                ) VALUES (
                    :sync_type, :sync_mode, :filter_from_date,
                    :rows_created, :rows_updated, :rows_unchanged, :rows_failed,
                    :status, :error_log, :started_at, :finished_at
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':sync_type', (string)($data['sync_type'] ?? 'items'));
        $stmt->bindValue(':sync_mode', (string)($data['sync_mode'] ?? 'incremental'));
        $stmt->bindValue(':filter_from_date', $data['filter_from_date'] ?? null);
        $stmt->bindValue(':rows_created', (int)($data['rows_created'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_updated', (int)($data['rows_updated'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_unchanged', (int)($data['rows_unchanged'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_failed', (int)($data['rows_failed'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':status', (string)($data['status'] ?? 'running'));
        $stmt->bindValue(':error_log', $data['error_log'] ?? null);
        $stmt->bindValue(':started_at', (string)($data['started_at'] ?? date('Y-m-d H:i:s')));
        $stmt->bindValue(':finished_at', $data['finished_at'] ?? null);
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        if ($id <= 0 || !$this->tableExists()) {
            return false;
        }
        $sql = 'UPDATE inv_inventory_sap_sync_runs SET
                    rows_created = :rows_created,
                    rows_updated = :rows_updated,
                    rows_unchanged = :rows_unchanged,
                    rows_failed = :rows_failed,
                    status = :status,
                    error_log = :error_log,
                    finished_at = :finished_at';
        if ($this->hasProgressColumns()) {
            $sql .= ',
                    progress_examined = COALESCE(:progress_examined, progress_examined),
                    progress_total = COALESCE(:progress_total, progress_total),
                    progress_label = COALESCE(:progress_label, progress_label),
                    result_message = COALESCE(:result_message, result_message)';
        }
        $sql .= ' WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':rows_created', (int)($data['rows_created'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_updated', (int)($data['rows_updated'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_unchanged', (int)($data['rows_unchanged'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_failed', (int)($data['rows_failed'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':status', (string)($data['status'] ?? 'completed'));
        $stmt->bindValue(':error_log', $data['error_log'] ?? null);
        $stmt->bindValue(':finished_at', $data['finished_at'] ?? null);
        if ($this->hasProgressColumns()) {
            $stmt->bindValue(':progress_examined', isset($data['progress_examined']) ? (int)$data['progress_examined'] : null, isset($data['progress_examined']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':progress_total', isset($data['progress_total']) ? (int)$data['progress_total'] : null, isset($data['progress_total']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':progress_label', $data['progress_label'] ?? null);
            $stmt->bindValue(':result_message', $data['result_message'] ?? null);
        }
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateProgress(int $id, array $data): bool
    {
        if ($id <= 0 || !$this->tableExists() || !$this->hasProgressColumns()) {
            return false;
        }

        $sql = 'UPDATE inv_inventory_sap_sync_runs SET
                    progress_examined = :progress_examined,
                    progress_total = :progress_total,
                    progress_label = :progress_label,
                    rows_created = :rows_created,
                    rows_updated = :rows_updated,
                    rows_unchanged = :rows_unchanged,
                    rows_failed = :rows_failed
                WHERE id = :id AND status = :status_running';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':progress_examined', (int)($data['progress_examined'] ?? 0), PDO::PARAM_INT);
        $total = $data['progress_total'] ?? null;
        $stmt->bindValue(':progress_total', $total !== null && (int)$total > 0 ? (int)$total : null, $total !== null && (int)$total > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':progress_label', $data['progress_label'] ?? null);
        $stmt->bindValue(':rows_created', (int)($data['rows_created'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_updated', (int)($data['rows_updated'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_unchanged', (int)($data['rows_unchanged'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_failed', (int)($data['rows_failed'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':status_running', 'running');
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function requestCancel(int $id): bool
    {
        if ($id <= 0 || !$this->tableExists()) {
            return false;
        }

        $sql = 'UPDATE inv_inventory_sap_sync_runs
                SET status = :status_cancelled,
                    finished_at = NOW(),
                    result_message = :result_message,
                    progress_label = :progress_label,
                    error_log = NULL
                WHERE id = :id AND status = :status_running';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':status_cancelled', 'cancelled');
        $stmt->bindValue(':status_running', 'running');
        $stmt->bindValue(':result_message', 'Sincronização cancelada pelo usuário.');
        $stmt->bindValue(':progress_label', 'Cancelado');
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function isCancelRequested(int $id): bool
    {
        $run = $this->getById($id);

        return is_array($run) && (string)($run['status'] ?? '') === 'cancelled';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        if ($id <= 0 || !$this->tableExists()) {
            return null;
        }

        $sql = 'SELECT * FROM inv_inventory_sap_sync_runs WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed>|null $run
     * @return array<string, mixed>|null
     */
    public static function buildStatusPayload(?array $run): ?array
    {
        if ($run === null) {
            return null;
        }

        $examined = (int)($run['progress_examined'] ?? 0);
        $total = (int)($run['progress_total'] ?? 0);
        $label = (string)($run['progress_label'] ?? '');
        $status = (string)($run['status'] ?? '');
        $finished = in_array($status, ['completed', 'failed', 'cancelled'], true);
        $startedAt = strtotime((string)($run['started_at'] ?? '')) ?: time();
        $elapsed = max(0, time() - $startedAt);

        $syncPass = 1;
        if (preg_match('/Passagem (\d+)/u', $label, $passMatch) === 1) {
            $syncPass = max(1, (int)$passMatch[1]);
        }

        $displayExamined = $examined;
        if ($total > 0 && $examined > $total) {
            $remainder = $examined % $total;
            $displayExamined = $remainder === 0 ? $total : $remainder;
        }

        $percent = 0;
        if ($finished) {
            $percent = 100;
        } elseif ($total > 0 && $displayExamined > 0) {
            $percent = min(99, (int)round(($displayExamined / $total) * 100));
        } elseif ($displayExamined > 0) {
            $percent = min(95, max(5, (int)round(log10($displayExamined + 1) * 20)));
        }

        $etaSeconds = null;
        $etaLabel = null;
        if (!$finished && $total > $displayExamined && $displayExamined > 0 && $elapsed > 3) {
            $etaSeconds = (int)round((($total - $displayExamined) * $elapsed) / $displayExamined);
            $etaLabel = self::formatDuration($etaSeconds);
        }

        $success = $finished ? ($status === 'completed') : null;
        if ($status === 'cancelled') {
            $success = false;
        }
        $message = $finished
            ? (string)($run['result_message'] ?? $run['error_log'] ?? '')
            : null;

        $runId = (int)($run['id'] ?? 0);
        $failedItems = self::loadFailureEntriesForRun($runId, (string)($run['error_log'] ?? ''));
        $failureLogFile = $runId > 0 && is_file(self::failureLogFilePath($runId))
            ? ('sap_sync_failures_' . $runId . '.log')
            : null;

        return [
            'run_id' => (int)($run['id'] ?? 0),
            'sync_type' => (string)($run['sync_type'] ?? ''),
            'status' => $status,
            'finished' => $finished,
            'success' => $success,
            'percent' => $percent,
            'examined' => $displayExamined,
            'examined_lifetime' => $examined,
            'sync_pass' => $syncPass,
            'current_phase' => (string)($run['current_phase'] ?? ''),
            'total' => $total > 0 ? $total : null,
            'label' => $label,
            'created' => (int)($run['rows_created'] ?? 0),
            'updated' => (int)($run['rows_updated'] ?? 0),
            'unchanged' => (int)($run['rows_unchanged'] ?? 0),
            'failed' => (int)($run['rows_failed'] ?? 0),
            'failed_items' => $failedItems,
            'failure_log_file' => $failureLogFile,
            'elapsed_seconds' => $elapsed,
            'elapsed_label' => self::formatDuration($elapsed),
            'eta_seconds' => $etaSeconds,
            'eta_label' => $etaLabel,
            'message' => $message !== '' ? $message : null,
        ];
    }

    public static function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        if ($seconds < 45) {
            return 'menos de 1 min';
        }
        if ($seconds < 3600) {
            return '≈ ' . max(1, (int)ceil($seconds / 60)) . ' min';
        }

        $hours = intdiv($seconds, 3600);
        $mins = (int)ceil(($seconds % 3600) / 60);

        return '≈ ' . $hours . ' h ' . $mins . ' min';
    }

    public function hasActiveRun(?int $exceptRunId = null): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $sql = 'SELECT 1 FROM inv_inventory_sap_sync_runs WHERE status = :running';
        $params = [':running' => 'running'];
        if ($exceptRunId !== null && $exceptRunId > 0) {
            $sql .= ' AND id <> :except_id';
            $params[':except_id'] = $exceptRunId;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Encerra execuções "running" sem progresso (ex.: fase execute não chegou a rodar).
     */
    public function expireStaleActiveRuns(int $maxIdleSeconds = 120): int
    {
        if ($maxIdleSeconds < 30 || !$this->tableExists()) {
            return 0;
        }

        $sql = 'UPDATE inv_inventory_sap_sync_runs
                SET status = :failed,
                    finished_at = NOW(),
                    result_message = :result_message,
                    progress_label = :progress_label
                WHERE status = :running
                  AND COALESCE(progress_examined, 0) = 0
                  AND started_at IS NOT NULL
                  AND started_at < DATE_SUB(NOW(), INTERVAL :seconds SECOND)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':failed', 'failed');
        $stmt->bindValue(':running', 'running');
        $stmt->bindValue(':seconds', $maxIdleSeconds, PDO::PARAM_INT);
        $stmt->bindValue(':result_message', 'Execução expirada por inatividade. Inicie a sincronização novamente.');
        $stmt->bindValue(':progress_label', 'Expirado');
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Encerra execuções "running" há muito tempo (processo PHP órfão em background).
     */
    public function expireLongRunningRuns(int $maxSeconds = 7200): int
    {
        if ($maxSeconds < 300 || !$this->tableExists()) {
            return 0;
        }

        $sql = 'UPDATE inv_inventory_sap_sync_runs
                SET status = :failed,
                    finished_at = NOW(),
                    result_message = :result_message,
                    progress_label = :progress_label
                WHERE status = :running
                  AND started_at IS NOT NULL
                  AND started_at < DATE_SUB(NOW(), INTERVAL :seconds SECOND)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':failed', 'failed');
        $stmt->bindValue(':running', 'running');
        $stmt->bindValue(':seconds', $maxSeconds, PDO::PARAM_INT);
        $stmt->bindValue(
            ':result_message',
            'Execução expirada por tempo máximo. Reinicie a sincronização se necessário.'
        );
        $stmt->bindValue(':progress_label', 'Expirado');
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function setCurrentPhase(int $runId, ?string $phase): void
    {
        if ($runId <= 0 || !$this->tableExists() || !$this->hasColumn('current_phase')) {
            return;
        }

        $sql = 'UPDATE inv_inventory_sap_sync_runs SET current_phase = :phase WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $runId, PDO::PARAM_INT);
        $stmt->bindValue(':phase', $phase);
        $stmt->execute();
    }

    private function hasColumn(string $column): bool
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT 1 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = \'inv_inventory_sap_sync_runs\'
                   AND column_name = :column
                 LIMIT 1'
            );
            $stmt->bindValue(':column', $column);
            $stmt->execute();

            return (bool)$stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }

    public function getLastSuccessfulItemsSyncDate(): ?string
    {
        if (!$this->tableExists()) {
            return null;
        }

        $sql = 'SELECT finished_at FROM inv_inventory_sap_sync_runs
                WHERE sync_type IN (\'items\', \'all\')
                  AND status = \'completed\'
                  AND finished_at IS NOT NULL
                ORDER BY finished_at DESC
                LIMIT 1';
        $stmt = $this->getConnection()->query($sql);
        $value = $stmt->fetchColumn();

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function hasProgressColumns(): bool
    {
        try {
            $stmt = $this->getConnection()->query(
                "SELECT 1 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = 'inv_inventory_sap_sync_runs'
                   AND column_name = 'progress_examined'
                 LIMIT 1"
            );

            return (bool)$stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }

    public function getLastSuccessfulFinishedAt(string $syncType): ?string
    {
        if (!$this->tableExists()) {
            return null;
        }

        $sql = 'SELECT finished_at FROM inv_inventory_sap_sync_runs
                WHERE sync_type = :sync_type AND status = :status
                ORDER BY finished_at DESC, id DESC
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':sync_type', $syncType);
        $stmt->bindValue(':status', 'completed');
        $stmt->execute();
        $value = $stmt->fetchColumn();

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function getLastPartialCheckpoint(string $syncType): ?string
    {
        if (!$this->tableExists()) {
            return null;
        }

        $sql = 'SELECT error_log FROM inv_inventory_sap_sync_runs
                WHERE sync_type = :sync_type
                  AND status = :status
                  AND error_log LIKE :prefix
                ORDER BY id DESC
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':sync_type', $syncType);
        $stmt->bindValue(':status', 'completed');
        $stmt->bindValue(':prefix', 'checkpoint:%');
        $stmt->execute();
        $value = $stmt->fetchColumn();
        if (!is_string($value) || !str_starts_with($value, 'checkpoint:')) {
            return null;
        }

        $rest = substr($value, strlen('checkpoint:'));
        $code = trim(explode('|', $rest, 2)[0] ?? '');

        return $code !== '' ? $code : null;
    }

    public function clearPartialCheckpoints(string $syncType): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $sql = 'UPDATE inv_inventory_sap_sync_runs
                SET error_log = NULL
                WHERE sync_type = :sync_type AND error_log LIKE :prefix';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':sync_type', $syncType);
        $stmt->bindValue(':prefix', 'checkpoint:%');
        $stmt->execute();
    }

    public static function failureLogFilePath(int $runId): string
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4);

        return $root . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'sap_sync_failures_' . $runId . '.log';
    }

    /**
     * @return list<array{erp_code: string, phase: string, reason: string, at: string}>
     */
    public static function loadFailureEntriesForRun(int $runId, ?string $errorLog = null): array
    {
        if ($runId <= 0) {
            return [];
        }

        $fromFile = self::parseFailureLogFile(self::failureLogFilePath($runId));
        if ($fromFile !== []) {
            return $fromFile;
        }

        return self::parseFailureEntriesFromErrorLog($errorLog);
    }

    /**
     * @return list<array{erp_code: string, phase: string, reason: string, at: string}>
     */
    public static function parseFailureLogFile(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $content = @file_get_contents($path);
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $entries = [];
        foreach (preg_split('/\R/u', $content) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match(
                '/^\[(?<at>[^\]]+)\]\s+(?<phase>\w+)\s+\|\s+(?<erp_code>[^|]+)\s+\|\s+(?<reason>.+)$/u',
                $line,
                $matches
            ) !== 1) {
                continue;
            }

            $entries[] = [
                'erp_code' => trim($matches['erp_code']),
                'phase' => trim($matches['phase']),
                'reason' => trim($matches['reason']),
                'at' => trim($matches['at']),
            ];
        }

        return $entries;
    }

    /**
     * @return list<array{erp_code: string, phase: string, reason: string, at: string}>
     */
    public static function parseFailureEntriesFromErrorLog(?string $errorLog): array
    {
        if (!is_string($errorLog) || $errorLog === '') {
            return [];
        }

        foreach (preg_split('/\R/u', $errorLog) as $line) {
            $line = trim($line);
            if (!str_starts_with($line, 'failures:')) {
                continue;
            }

            $json = substr($line, strlen('failures:'));
            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                return [];
            }

            $entries = [];
            foreach ($decoded as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $erpCode = trim((string)($row['erp_code'] ?? ''));
                if ($erpCode === '') {
                    continue;
                }
                $entries[] = [
                    'erp_code' => $erpCode,
                    'phase' => trim((string)($row['phase'] ?? 'items')),
                    'reason' => trim((string)($row['reason'] ?? '')),
                    'at' => trim((string)($row['at'] ?? '')),
                ];
            }

            return $entries;
        }

        return [];
    }

    public static function mergeErrorLogWithFailures(?string $existingErrorLog, array $failureEntries): ?string
    {
        $lines = [];
        if (is_string($existingErrorLog) && trim($existingErrorLog) !== '') {
            foreach (preg_split('/\R/u', trim($existingErrorLog)) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, 'failures:')) {
                    continue;
                }
                $lines[] = $line;
            }
        }

        if ($failureEntries !== []) {
            $lines[] = 'failures:' . json_encode(
                array_values($failureEntries),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        return $lines === [] ? null : implode("\n", $lines);
    }

    private function tableExists(): bool
    {
        try {
            $stmt = $this->getConnection()->query(
                "SELECT 1 FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = 'inv_inventory_sap_sync_runs'
                 LIMIT 1"
            );

            return (bool)$stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }
}
