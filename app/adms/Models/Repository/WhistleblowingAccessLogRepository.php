<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class WhistleblowingAccessLogRepository extends DbConnection
{
    public function log(int $reportId, int $userId, string $action): void
    {
        $sql = 'INSERT INTO adms_whistleblowing_access_log (report_id, user_id, action, created_at)
                VALUES (:report_id, :user_id, :action, :created_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':report_id' => $reportId,
            ':user_id' => $userId,
            ':action' => $action,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
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
}
