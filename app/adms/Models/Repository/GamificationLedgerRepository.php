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

    /**
     * Soma de pontos por utilizador (ranking).
     * Critério de desempate: quem atingiu a pontuação atual primeiro.
     *
     * @return list<array{user_id:int,user_name:string,user_image:?string,total_points:int,reached_at:string}>
     */
    public function getLeaderboard(int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        $sql = "SELECT l.user_id,
                       u.name AS user_name,
                       u.image AS user_image,
                       SUM(l.points) AS total_points,
                       MAX(l.created_at) AS reached_at
                FROM adms_gamification_point_ledger l
                INNER JOIN adms_users u ON u.id = l.user_id AND u.status = 1
                GROUP BY l.user_id, u.name, u.image
                ORDER BY total_points DESC, reached_at ASC, u.name ASC
                LIMIT {$limit}";
        $stmt = $this->getConnection()->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;

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
}
