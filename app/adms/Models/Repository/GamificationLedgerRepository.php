<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
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
        return $this->insertIfNotExistsAt($userId, $sourceType, $eventKey, $refType, $refId, $points, $metaJson, date('Y-m-d H:i:s'));
    }

    /**
     * Idempotente (uk user_id+event_key+ref_type+ref_id). Use para backfill com data real da ação.
     *
     * @param string $createdAt Data/hora MySQL (Y-m-d H:i:s) da atividade na timeline
     */
    public function insertIfNotExistsAt(
        int $userId,
        string $sourceType,
        string $eventKey,
        string $refType,
        int $refId,
        int $points,
        ?string $metaJson,
        string $createdAt
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
        $at = $this->normalizeLedgerCreatedAt($createdAt);
        if ($at === null) {
            return false;
        }
        $sql = 'INSERT IGNORE INTO adms_gamification_point_ledger
                (user_id, source_type, event_key, ref_type, ref_id, points, meta_json, created_at)
                VALUES (:uid, :st, :ek, :rt, :rid, :pts, :meta, :cat)';
        $stmt = $this->getConnection()->prepare($sql);

        return $stmt->execute([
            ':uid' => $userId,
            ':st' => $sourceType,
            ':ek' => $eventKey,
            ':rt' => $refType,
            ':rid' => $refId,
            ':pts' => $points,
            ':meta' => $metaJson,
            ':cat' => $at,
        ]) && $stmt->rowCount() > 0;
    }

    private function normalizeLedgerCreatedAt(string $createdAt): ?string
    {
        $createdAt = trim($createdAt);
        if ($createdAt === '') {
            return null;
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $createdAt)
            ?: \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sP', $createdAt)
            ?: \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $createdAt);
        if ($dt === false) {
            try {
                $dt = new \DateTimeImmutable($createdAt);
            } catch (\Throwable) {
                return null;
            }
        }

        return $dt->format('Y-m-d H:i:s');
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
        // «Geral» = soma de todo o histórico; mês do formulário só aplica a «Mensal» e «Por setor».
        if ($scope === 'monthly' || $scope === 'department') {
            $where[] = 'DATE_FORMAT(l.created_at, "%Y-%m") = :month_ref';
        }
        if ($departmentId !== null && $departmentId > 0) {
            $where[] = 'u.user_department_id = :department_id';
        }
        $where[] = GamificationRankingExclusions::sqlLedgerUserNotExcluded('l.user_id');
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

    /**
     * Remove pontos do ledger ligados a um post da timeline (post, comentários desse post,
     * reações e votos em enquete usam ref_id = id do post ou id do comentário).
     * Deve ser chamado antes de apagar comentários do post (usa subquery em adms_timeline_comments).
     *
     * @return int Linhas apagadas (aprox.: soma dos dois DELETEs)
     */
    public function deleteLedgerRowsForTimelinePost(int $postId): int
    {
        if ($postId <= 0) {
            return 0;
        }
        $pdo = $this->getConnection();
        $stmtC = $pdo->prepare(
            'DELETE FROM adms_gamification_point_ledger
             WHERE ref_type = \'timeline_comment\'
               AND ref_id IN (SELECT id FROM adms_timeline_comments WHERE post_id = :pid)'
        );
        $stmtC->execute([':pid' => $postId]);
        $n = $stmtC->rowCount();

        $stmtP = $pdo->prepare(
            'DELETE FROM adms_gamification_point_ledger
             WHERE ref_type = \'timeline_post\' AND ref_id = :pid'
        );
        $stmtP->execute([':pid' => $postId]);
        $n += $stmtP->rowCount();

        if ($n > 0) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_gamification_point_ledger',
                $postId,
                $usuarioId,
                'DELETE',
                ['timeline_post_id' => (string) $postId, 'ledger_rows_removed' => (string) $n],
                []
            );
        }

        return $n;
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
