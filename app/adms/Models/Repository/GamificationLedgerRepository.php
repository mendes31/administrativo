<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class GamificationLedgerRepository extends DbConnection
{
    public function insertIfNotExists(
        int $userId,
        string $sourceType,
        string $eventKey,
        string $refType,
        int $refId,
        int $points,
        ?string $metaJson
    ): bool {
        if ($userId <= 0 || $points === 0) {
            return false;
        }
        $sourceType = mb_substr(trim($sourceType), 0, 32);
        $eventKey = mb_substr(trim($eventKey), 0, 64);
        $refType = mb_substr(trim($refType), 0, 64);
        if ($sourceType === '' || $eventKey === '') {
            return false;
        }
        $sql = 'INSERT IGNORE INTO adms_gamification_point_ledger
                (user_id, source_type, event_key, ref_type, ref_id, points, meta_json, created_at)
                VALUES (:uid, :st, :ek, :rt, :rid, :pts, :meta, NOW())';
        $stmt = $this->getConnection()->prepare($sql);

        return $stmt->execute([
            ':uid' => $userId,
            ':st' => $sourceType,
            ':ek' => $eventKey,
            ':rt' => $refType,
            ':rid' => $refId,
            ':pts' => $points,
            ':meta' => $metaJson,
        ]) && $stmt->rowCount() > 0;
    }

    public function countAwardsToday(int $userId, string $eventKey): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT COUNT(*) AS c FROM adms_gamification_point_ledger
             WHERE user_id = :u AND event_key = :e AND DATE(created_at) = CURDATE()'
        );
        $stmt->execute([':u' => $userId, ':e' => $eventKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['c'] ?? 0);
    }

    public function countAwardsSince(int $userId, string $eventKey, int $seconds): int
    {
        if ($userId <= 0 || $seconds <= 0) {
            return 0;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT COUNT(*) AS c FROM adms_gamification_point_ledger
             WHERE user_id = :u AND event_key = :e
               AND created_at >= (NOW() - INTERVAL :sec SECOND)'
        );
        $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':e', $eventKey, PDO::PARAM_STR);
        $stmt->bindValue(':sec', $seconds, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['c'] ?? 0);
    }

    /**
     * Soma de pontos por utilizador (ranking).
     * Critério de desempate: quem atingiu a pontuação atual primeiro.
     *
     * @return list<array{user_id:int,user_name:string,user_image:?string,total_points:int,reached_at:string}>
     */
    public function getLeaderboard(
        int $limit = 30,
        string $scope = 'general',
        ?int $departmentId = null,
        ?string $monthRef = null
    ): array
    {
        $limit = max(1, min(100, $limit));
        $scope = in_array($scope, ['general', 'monthly', 'department'], true) ? $scope : 'general';
        $where = [];
        $monthRef = preg_match('/^\d{4}-\d{2}$/', (string)$monthRef) ? (string)$monthRef : date('Y-m');
        if ($scope === 'monthly' || $monthRef !== '') {
            $where[] = 'DATE_FORMAT(l.created_at, "%Y-%m") = :month_ref';
        }
        if ($departmentId !== null && $departmentId > 0) {
            $where[] = 'u.user_department_id = :department_id';
        }
        $whereSql = $where !== [] ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT l.user_id,
                       u.name AS user_name,
                       u.image AS user_image,
                       u.user_department_id,
                       SUM(l.points) AS total_points,
                       MAX(l.created_at) AS reached_at
                FROM adms_gamification_point_ledger l
                INNER JOIN adms_users u ON u.id = l.user_id AND u.status = 1
                {$whereSql}
                GROUP BY l.user_id, u.name, u.image, u.user_department_id
                ORDER BY total_points DESC, reached_at ASC, u.name ASC
                LIMIT {$limit}";
        $stmt = $this->getConnection()->prepare($sql);
        if (str_contains($whereSql, ':month_ref')) {
            $stmt->bindValue(':month_ref', $monthRef, PDO::PARAM_STR);
        }
        if ($departmentId !== null && $departmentId > 0) {
            $stmt->bindValue(':department_id', $departmentId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    public function countAwardsTotal(int $userId, string $eventKey): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT COUNT(*) AS c FROM adms_gamification_point_ledger WHERE user_id = :u AND event_key = :e'
        );
        $stmt->execute([':u' => $userId, ':e' => $eventKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['c'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRecent(?int $userId, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        if ($userId !== null && $userId > 0) {
            $stmt = $this->getConnection()->prepare(
                "SELECT l.id, l.user_id, u.name AS user_name, l.source_type, l.event_key, l.ref_type, l.ref_id, l.points, l.created_at
                 FROM adms_gamification_point_ledger l
                 INNER JOIN adms_users u ON u.id = l.user_id
                 WHERE l.user_id = :u
                 ORDER BY l.id DESC
                 LIMIT {$limit}"
            );
            $stmt->execute([':u' => $userId]);
        } else {
            $stmt = $this->getConnection()->query(
                "SELECT l.id, l.user_id, u.name AS user_name, l.source_type, l.event_key, l.ref_type, l.ref_id, l.points, l.created_at
                 FROM adms_gamification_point_ledger l
                 INNER JOIN adms_users u ON u.id = l.user_id
                 ORDER BY l.id DESC
                 LIMIT {$limit}"
            );
        }
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;

        return is_array($rows) ? $rows : [];
    }

    public function getDepartmentRankingOptions(): array
    {
        $stmt = $this->getConnection()->query(
            'SELECT d.id, d.name
             FROM adms_departments d
             INNER JOIN adms_users u ON u.user_department_id = d.id AND u.status = 1
             GROUP BY d.id, d.name
             ORDER BY d.name ASC'
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
        return is_array($rows) ? $rows : [];
    }
}
