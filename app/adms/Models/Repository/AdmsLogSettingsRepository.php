<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class AdmsLogSettingsRepository extends DbConnection
{
    /**
     * Cache em memória por requisição para evitar consultas repetidas.
     */
    private static ?array $cachedSettings = null;

    public function getSettings(): array
    {
        if (self::$cachedSettings !== null) {
            return self::$cachedSettings;
        }

        $sql = 'SELECT * FROM adms_log_settings ORDER BY id DESC LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        if ($row === []) {
            $this->ensureDefaultRow();
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        self::$cachedSettings = $row;
        return $row;
    }

    public function saveSettings(array $data): bool
    {
        $current = $this->getSettings();
        $sessionDebug = (int)($data['session_debug_logs'] ?? 0) === 1 ? 1 : 0;
        $slowProfiler = (int)($data['slow_request_profiler_enabled'] ?? 0) === 1 ? 1 : 0;
        $thresholdMs = (int)($data['slow_request_threshold_ms'] ?? 700);
        if ($thresholdMs < 10) {
            $thresholdMs = 10;
        }
        if ($thresholdMs > 30000) {
            $thresholdMs = 30000;
        }
        $retentionDays = (int)($data['slow_request_retention_days'] ?? 7);
        if ($retentionDays < 1) {
            $retentionDays = 1;
        }
        if ($retentionDays > 60) {
            $retentionDays = 60;
        }

        if (!empty($current['id'])) {
            $sql = 'UPDATE adms_log_settings
                    SET session_debug_logs = :session_debug_logs,
                        slow_request_profiler_enabled = :slow_request_profiler_enabled,
                        slow_request_threshold_ms = :slow_request_threshold_ms,
                        slow_request_retention_days = :slow_request_retention_days,
                        updated_at = NOW()
                    WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', (int)$current['id'], PDO::PARAM_INT);
        } else {
            $sql = 'INSERT INTO adms_log_settings (
                        session_debug_logs,
                        slow_request_profiler_enabled,
                        slow_request_threshold_ms,
                        slow_request_retention_days,
                        created_at,
                        updated_at
                    ) VALUES (
                        :session_debug_logs,
                        :slow_request_profiler_enabled,
                        :slow_request_threshold_ms,
                        :slow_request_retention_days,
                        NOW(),
                        NOW()
                    )';
            $stmt = $this->getConnection()->prepare($sql);
        }

        $stmt->bindValue(':session_debug_logs', $sessionDebug, PDO::PARAM_INT);
        $stmt->bindValue(':slow_request_profiler_enabled', $slowProfiler, PDO::PARAM_INT);
        $stmt->bindValue(':slow_request_threshold_ms', $thresholdMs, PDO::PARAM_INT);
        $stmt->bindValue(':slow_request_retention_days', $retentionDays, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok) {
            self::$cachedSettings = null;
        }
        return $ok;
    }

    public function isSessionDebugEnabled(): bool
    {
        $settings = $this->getSettings();
        return (int)($settings['session_debug_logs'] ?? 0) === 1;
    }

    public function isSlowProfilerEnabled(): bool
    {
        $settings = $this->getSettings();
        return (int)($settings['slow_request_profiler_enabled'] ?? 0) === 1;
    }

    public function getSlowProfilerThresholdMs(): int
    {
        $settings = $this->getSettings();
        $v = (int)($settings['slow_request_threshold_ms'] ?? 700);
        return $v > 0 ? $v : 700;
    }

    public function getSlowProfilerRetentionDays(): int
    {
        $settings = $this->getSettings();
        $v = (int)($settings['slow_request_retention_days'] ?? 7);
        return $v > 0 ? $v : 7;
    }

    private function ensureDefaultRow(): void
    {
        $sql = 'INSERT INTO adms_log_settings (
                    id,
                    session_debug_logs,
                    slow_request_profiler_enabled,
                    slow_request_threshold_ms,
                    slow_request_retention_days,
                    created_at,
                    updated_at
                )
                VALUES (1, 0, 0, 700, 7, NOW(), NOW())
                ON DUPLICATE KEY UPDATE updated_at = updated_at';
        $this->getConnection()->exec($sql);
    }
}

