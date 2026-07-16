<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Models\Services\WhistleblowingChannelSecurityService;
use PDO;

/**
 * Configuração do Canal de Denúncias (cron LGPD + chave de criptografia).
 */
class WhistleblowingConfigRepository extends DbConnection
{
    private static ?array $cachedRow = null;

    public static function clearCache(): void
    {
        self::$cachedRow = null;
    }

    /** @return array<string, mixed> */
    public function getRow(): array
    {
        if (self::$cachedRow !== null) {
            return self::$cachedRow;
        }

        try {
            if (!$this->tableExists()) {
                self::$cachedRow = [];

                return [];
            }
            $stmt = $this->getConnection()->query('SELECT * FROM adms_whistleblowing_config ORDER BY id ASC LIMIT 1');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            self::$cachedRow = $row ?: [];
        } catch (\Throwable) {
            self::$cachedRow = [];
        }

        return self::$cachedRow;
    }

    public function getHttpCronToken(): string
    {
        return trim((string) ($this->getRow()['http_cron_token'] ?? ''));
    }

    public function hasHttpCronToken(): bool
    {
        return $this->getHttpCronToken() !== '';
    }

    public function getEncryptionKey(): string
    {
        return trim((string) ($this->getRow()['encryption_key'] ?? ''));
    }

    public function hasEncryptionKey(): bool
    {
        return WhistleblowingChannelSecurityService::hasStrongEncryptionKey();
    }

    public function getRetentionArchiveYears(): int
    {
        $v = (int) ($this->getRow()['retention_archive_years'] ?? 5);

        return max(1, min(30, $v > 0 ? $v : 5));
    }

    public function getRetentionDeleteYears(): int
    {
        $v = (int) ($this->getRow()['retention_delete_years'] ?? 10);

        return max(2, min(50, $v > 0 ? $v : 10));
    }

    public function isCronEnabled(): bool
    {
        $row = $this->getRow();
        if (!array_key_exists('cron_enabled', $row)) {
            return true;
        }

        return (int) ($row['cron_enabled'] ?? 1) === 1;
    }

    public function getCronTime(): string
    {
        $t = trim((string) ($this->getRow()['cron_time'] ?? '02:00'));
        if (preg_match('/^\d{2}:\d{2}$/', $t)) {
            return $t;
        }

        return '02:00';
    }

    /** @return array{hour: int, minute: int} */
    public function getCronTimeParts(): array
    {
        [$h, $m] = array_pad(explode(':', $this->getCronTime(), 2), 2, '0');

        return ['hour' => (int) $h, 'minute' => (int) $m];
    }

    public function getRateLimitMaxAttempts(): int
    {
        return (int) ($this->getRow()['rate_limit_max_attempts'] ?? 5);
    }

    public function getRateLimitWindowMinutes(): int
    {
        return (int) ($this->getRow()['rate_limit_window_minutes'] ?? 15);
    }

    public function getSlaFirstResponseHours(): int
    {
        return max(1, min(720, (int) ($this->getRow()['sla_first_response_hours'] ?? 72)));
    }

    public function getSlaHoursCritico(): int
    {
        return max(1, min(720, (int) ($this->getRow()['sla_hours_critico'] ?? 24)));
    }

    public function getSlaHoursAlto(): int
    {
        return max(1, min(720, (int) ($this->getRow()['sla_hours_alto'] ?? 48)));
    }

    public function getSlaHoursMedio(): int
    {
        return max(1, min(720, (int) ($this->getRow()['sla_hours_medio'] ?? 72)));
    }

    public function getSlaHoursBaixo(): int
    {
        return max(1, min(720, (int) ($this->getRow()['sla_hours_baixo'] ?? 120)));
    }

    public function isNotifyCommitteeOnReply(): bool
    {
        return (int) ($this->getRow()['notify_committee_on_reply'] ?? 1) === 1;
    }

    public function isNotifyCommitteeOnStatusChange(): bool
    {
        return (int) ($this->getRow()['notify_committee_on_status_change'] ?? 1) === 1;
    }

    public function isNotifyCommitteeOnSlaBreach(): bool
    {
        return (int) ($this->getRow()['notify_committee_on_sla_breach'] ?? 1) === 1;
    }

    public function isNotifyReporterOnReply(): bool
    {
        return (int) ($this->getRow()['notify_reporter_on_reply'] ?? 0) === 1;
    }

    public function isCaptchaEnabled(): bool
    {
        return (int) ($this->getRow()['captcha_enabled'] ?? 0) === 1;
    }

    public function getCaptchaProvider(): string
    {
        $p = strtolower(trim((string) ($this->getRow()['captcha_provider'] ?? 'hcaptcha')));

        return $p === 'recaptcha' ? 'recaptcha' : 'hcaptcha';
    }

    public function getCaptchaSiteKey(): string
    {
        return trim((string) ($this->getRow()['captcha_site_key'] ?? ''));
    }

    public function getCaptchaSecretKey(): string
    {
        return trim((string) ($this->getRow()['captcha_secret_key'] ?? ''));
    }

    public function buildSuggestedCronLine(string $urlAdm): string
    {
        $token = $this->getHttpCronToken();
        if ($token === '') {
            $token = 'SEU_TOKEN';
        }
        $parts = $this->getCronTimeParts();
        $url = rtrim($urlAdm, '/') . '/whistleblowing-retention-cron?token=' . $token;

        return sprintf('%d %d * * * curl -fsS "%s"', $parts['minute'], $parts['hour'], $url);
    }

    /**
     * @param array<string, mixed> $policies
     */
    public function savePolicies(array $policies): bool
    {
        $archiveYears = max(1, min(30, (int) ($policies['retention_archive_years'] ?? 5)));
        $deleteYears = max($archiveYears + 1, min(50, (int) ($policies['retention_delete_years'] ?? 10)));
        $cronTime = trim((string) ($policies['cron_time'] ?? '02:00'));
        if (!preg_match('/^\d{2}:\d{2}$/', $cronTime)) {
            $cronTime = '02:00';
        }
        [$h, $m] = array_map('intval', explode(':', $cronTime, 2));
        if ($h < 0 || $h > 23 || $m < 0 || $m > 59) {
            $cronTime = '02:00';
        }

        return $this->saveFields([
            'retention_archive_years' => (string) $archiveYears,
            'retention_delete_years' => (string) $deleteYears,
            'cron_enabled' => !empty($policies['cron_enabled']) ? '1' : '0',
            'cron_time' => $cronTime,
            'rate_limit_max_attempts' => (string) max(3, min(20, (int) ($policies['rate_limit_max_attempts'] ?? 5))),
            'rate_limit_window_minutes' => (string) max(5, min(120, (int) ($policies['rate_limit_window_minutes'] ?? 15))),
            'sla_first_response_hours' => (string) max(1, min(720, (int) ($policies['sla_first_response_hours'] ?? 72))),
            'sla_hours_critico' => (string) max(1, min(720, (int) ($policies['sla_hours_critico'] ?? 24))),
            'sla_hours_alto' => (string) max(1, min(720, (int) ($policies['sla_hours_alto'] ?? 48))),
            'sla_hours_medio' => (string) max(1, min(720, (int) ($policies['sla_hours_medio'] ?? 72))),
            'sla_hours_baixo' => (string) max(1, min(720, (int) ($policies['sla_hours_baixo'] ?? 120))),
            'notify_committee_on_reply' => !empty($policies['notify_committee_on_reply']) ? '1' : '0',
            'notify_committee_on_status_change' => !empty($policies['notify_committee_on_status_change']) ? '1' : '0',
            'notify_committee_on_sla_breach' => !empty($policies['notify_committee_on_sla_breach']) ? '1' : '0',
            'notify_reporter_on_reply' => !empty($policies['notify_reporter_on_reply']) ? '1' : '0',
            'captcha_enabled' => !empty($policies['captcha_enabled']) ? '1' : '0',
            'captcha_provider' => in_array(($policies['captcha_provider'] ?? ''), ['recaptcha', 'hcaptcha'], true)
                ? $policies['captcha_provider']
                : 'hcaptcha',
            'captcha_site_key' => trim((string) ($policies['captcha_site_key'] ?? '')),
            'captcha_secret_key' => trim((string) ($policies['captcha_secret_key'] ?? '')),
        ]);
    }

    public function saveHttpCronToken(string $token): bool
    {
        return $this->saveFields(['http_cron_token' => trim($token)]);
    }

    public function saveEncryptionKey(string $key): bool
    {
        return $this->saveFields(['encryption_key' => trim($key)]);
    }

    /**
     * @param array<string, string|null> $fields
     */
    private function saveFields(array $fields): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $oldData = $this->getRow();
        $conn = $this->getConnection();
        $userId = (int) ($_SESSION['user_id'] ?? 1);

        if (!empty($oldData['id'])) {
            $sets = [];
            $params = [':id' => (int) $oldData['id']];
            foreach ($fields as $col => $val) {
                $sets[] = "{$col} = :{$col}";
                if (in_array($col, [
                    'retention_archive_years', 'retention_delete_years', 'cron_enabled',
                    'rate_limit_max_attempts', 'rate_limit_window_minutes',
                    'sla_first_response_hours', 'sla_hours_critico', 'sla_hours_alto', 'sla_hours_medio', 'sla_hours_baixo',
                    'notify_committee_on_reply', 'notify_committee_on_status_change', 'notify_committee_on_sla_breach',
                    'notify_reporter_on_reply', 'captcha_enabled',
                ], true)) {
                    $params[":{$col}"] = (int) $val;
                } else {
                    $params[":{$col}"] = ($val === '' || $val === null) ? null : $val;
                }
            }
            $sets[] = 'updated_at = NOW()';
            $sql = 'UPDATE adms_whistleblowing_config SET ' . implode(', ', $sets) . ' WHERE id = :id';
            $stmt = $conn->prepare($sql);
            foreach ($params as $k => $v) {
                if ($v === null) {
                    $stmt->bindValue($k, $v, PDO::PARAM_NULL);
                } elseif (is_int($v)) {
                    $stmt->bindValue($k, $v, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($k, $v, PDO::PARAM_STR);
                }
            }
            $ok = $stmt->execute();
        } else {
            $cols = array_keys($fields);
            $placeholders = array_map(fn ($c) => ':' . $c, $cols);
            $sql = 'INSERT INTO adms_whistleblowing_config (' . implode(', ', $cols) . ', created_at, updated_at)
                    VALUES (' . implode(', ', $placeholders) . ', NOW(), NOW())';
            $stmt = $conn->prepare($sql);
            foreach ($fields as $col => $val) {
                if (in_array($col, [
                    'retention_archive_years', 'retention_delete_years', 'cron_enabled',
                    'rate_limit_max_attempts', 'rate_limit_window_minutes',
                    'sla_first_response_hours', 'sla_hours_critico', 'sla_hours_alto', 'sla_hours_medio', 'sla_hours_baixo',
                    'notify_committee_on_reply', 'notify_committee_on_status_change', 'notify_committee_on_sla_breach',
                    'notify_reporter_on_reply', 'captcha_enabled',
                ], true)) {
                    $stmt->bindValue(':' . $col, (int) $val, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':' . $col, ($val === '' || $val === null) ? null : $val, ($val === '' || $val === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                }
            }
            $ok = $stmt->execute();
        }

        if ($ok) {
            self::clearCache();
            $newData = $this->getRow();
            $recordId = (int) ($newData['id'] ?? $oldData['id'] ?? 0);
            if ($recordId > 0) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_whistleblowing_config',
                    $recordId,
                    $userId,
                    empty($oldData['id']) ? 'INSERT' : 'UPDATE',
                    $oldData,
                    $newData
                );
            }
        }

        return $ok;
    }

    private function tableExists(): bool
    {
        try {
            $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'adms_whistleblowing_config'");

            return (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }
}
