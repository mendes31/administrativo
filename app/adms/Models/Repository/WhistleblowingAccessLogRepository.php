<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Controllers\Services\RequestHelper;
use App\adms\Models\Services\DbConnection;
use PDO;

class WhistleblowingAccessLogRepository extends DbConnection
{
    private static ?bool $hasClientMeta = null;

    /**
     * @param int $dedupSeconds Se > 0, não grava se já existir registro idêntico
     *                          (mesmo report/usuário/ação) dentro da janela — evita
     *                          ruído de F5/prerender em ações de leitura.
     */
    public function log(int $reportId, int $userId, string $action, int $dedupSeconds = 0): void
    {
        $action = substr($action, 0, 60);

        if ($dedupSeconds > 0 && $this->hasRecentEntry($reportId, $userId, $action, $dedupSeconds)) {
            return;
        }

        $params = [
            ':report_id' => $reportId,
            ':user_id' => $userId,
            ':action' => $action,
            ':created_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->hasClientMetaColumns()) {
            $ip = substr(RequestHelper::getClientIp(), 0, 45);
            $uaRaw = (string) (RequestHelper::getUserAgent() ?? '');
            $ua = substr($uaRaw, 0, 512);
            $sql = 'INSERT INTO adms_whistleblowing_access_log
                        (report_id, user_id, action, ip_address, user_agent, created_at)
                    VALUES
                        (:report_id, :user_id, :action, :ip_address, :user_agent, :created_at)';
            $params[':ip_address'] = $ip !== '' ? $ip : null;
            $params[':user_agent'] = $ua !== '' ? $ua : null;
        } else {
            $sql = 'INSERT INTO adms_whistleblowing_access_log
                        (report_id, user_id, action, created_at)
                    VALUES
                        (:report_id, :user_id, :action, :created_at)';
        }

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getByReportId(int $reportId): array
    {
        $sql = 'SELECT al.*, u.name AS user_name
                FROM adms_whistleblowing_access_log al
                INNER JOIN adms_users u ON al.user_id = u.id
                WHERE al.report_id = :report_id
                ORDER BY al.created_at DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':report_id', $reportId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function hasRecentEntry(int $reportId, int $userId, string $action, int $seconds): bool
    {
        $sql = 'SELECT 1 FROM adms_whistleblowing_access_log
                WHERE report_id = :report_id AND user_id = :user_id AND action = :action
                  AND created_at >= :since
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':report_id' => $reportId,
            ':user_id' => $userId,
            ':action' => $action,
            ':since' => date('Y-m-d H:i:s', time() - $seconds),
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function hasClientMetaColumns(): bool
    {
        if (self::$hasClientMeta !== null) {
            return self::$hasClientMeta;
        }

        try {
            $stmt = $this->getConnection()->query("SHOW COLUMNS FROM adms_whistleblowing_access_log LIKE 'ip_address'");
            self::$hasClientMeta = $stmt !== false && $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (\Throwable) {
            self::$hasClientMeta = false;
        }

        return self::$hasClientMeta;
    }
}
