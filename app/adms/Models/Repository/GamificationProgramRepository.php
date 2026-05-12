<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class GamificationProgramRepository extends DbConnection
{
    public function badgeSlugExists(string $slug, ?int $ignoreId = null): bool
    {
        $slug = trim($slug);
        if ($slug === '') {
            return false;
        }
        if ($ignoreId !== null && $ignoreId > 0) {
            $stmt = $this->getConnection()->prepare(
                'SELECT id FROM adms_gamification_badges WHERE slug = :slug AND id <> :id LIMIT 1'
            );
            $stmt->execute([':slug' => $slug, ':id' => $ignoreId]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT id FROM adms_gamification_badges WHERE slug = :slug LIMIT 1'
        );
        $stmt->execute([':slug' => $slug]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listLevels(): array
    {
        $stmt = $this->getConnection()->query(
            'SELECT id, name, min_points, badge_color, sort_order, is_active
             FROM adms_gamification_levels
             ORDER BY min_points ASC, sort_order ASC, id ASC'
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
        return is_array($rows) ? $rows : [];
    }

    public function getUserTotalPoints(int $userId): int
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT COALESCE(SUM(points),0) AS total
             FROM adms_gamification_point_ledger
             WHERE user_id = :u'
        );
        $stmt->execute([':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function getUserMonthlyPoints(int $userId, string $monthRef): int
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT COALESCE(SUM(points),0) AS total
             FROM adms_gamification_point_ledger
             WHERE user_id = :u
               AND DATE_FORMAT(created_at, "%Y-%m") = :month_ref'
        );
        $stmt->execute([':u' => $userId, ':month_ref' => $monthRef]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function countCompletedMissions(int $userId): int
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT COUNT(*) AS c
             FROM adms_gamification_user_mission_progress
             WHERE user_id = :u AND is_completed = 1'
        );
        $stmt->execute([':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    /** Maior soma de pontos do ledger num único mês civil (Y-m). */
    public function getUserMaxMonthlyPointsSum(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT COALESCE(MAX(m_total), 0) AS mx
             FROM (
                 SELECT SUM(l.points) AS m_total
                 FROM adms_gamification_point_ledger l
                 WHERE l.user_id = :u
                 GROUP BY DATE_FORMAT(l.created_at, \'%Y-%m\')
             ) t'
        );
        $stmt->execute([':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['mx'] ?? 0);
    }

    /** Maior número de missões concluídas num mesmo mês (week_start_date = Y-m-01). */
    public function getUserMaxMonthlyMissionCompletions(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT COALESCE(MAX(cn), 0) AS mx
             FROM (
                 SELECT COUNT(*) AS cn
                 FROM adms_gamification_user_mission_progress
                 WHERE user_id = :u AND is_completed = 1
                 GROUP BY week_start_date
             ) t'
        );
        $stmt->execute([':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['mx'] ?? 0);
    }

    /**
     * Maior contagem de linhas do ledger com event_key num único mês (ex.: publicações).
     */
    public function getUserMaxMonthlyLedgerEventCount(int $userId, string $eventKey): int
    {
        if ($userId <= 0 || trim($eventKey) === '') {
            return 0;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT COALESCE(MAX(cn), 0) AS mx
             FROM (
                 SELECT COUNT(*) AS cn
                 FROM adms_gamification_point_ledger l
                 WHERE l.user_id = :u AND l.event_key = :ek
                 GROUP BY DATE_FORMAT(l.created_at, \'%Y-%m\')
             ) t'
        );
        $stmt->execute([':u' => $userId, ':ek' => mb_substr(trim($eventKey), 0, 64)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['mx'] ?? 0);
    }

    public function listActiveLevels(): array
    {
        $stmt = $this->getConnection()->query(
            'SELECT id, name, min_points, badge_color, sort_order
             FROM adms_gamification_levels
             WHERE is_active = 1
             ORDER BY min_points ASC, sort_order ASC'
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
        return is_array($rows) ? $rows : [];
    }

    public function resolveUserLevel(int $points): ?array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT id, name, min_points, badge_color
             FROM adms_gamification_levels
             WHERE is_active = 1 AND min_points <= :pts
             ORDER BY min_points DESC
             LIMIT 1'
        );
        $stmt->execute([':pts' => $points]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function listActiveBadges(): array
    {
        $stmt = $this->getConnection()->query(
            'SELECT id, name, slug, description, criteria_key, criteria_value_json, icon
             FROM adms_gamification_badges
             WHERE is_active = 1
             ORDER BY id ASC'
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
        return is_array($rows) ? $rows : [];
    }

    public function listBadges(): array
    {
        $stmt = $this->getConnection()->query(
            'SELECT id, name, slug, description, criteria_key, criteria_value_json, icon, is_active
             FROM adms_gamification_badges
             ORDER BY id ASC'
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
        return is_array($rows) ? $rows : [];
    }

    public function userHasBadge(int $userId, int $badgeId): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT id FROM adms_gamification_user_badges WHERE user_id = :u AND badge_id = :b LIMIT 1'
        );
        $stmt->execute([':u' => $userId, ':b' => $badgeId]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function awardBadge(int $userId, int $badgeId, ?string $metaJson = null): bool
    {
        $stmt = $this->getConnection()->prepare(
            'INSERT IGNORE INTO adms_gamification_user_badges (user_id, badge_id, meta_json, awarded_at)
             VALUES (:u, :b, :meta, NOW())'
        );
        $executed = $stmt->execute([':u' => $userId, ':b' => $badgeId, ':meta' => $metaJson]);
        if (!$executed || $stmt->rowCount() <= 0) {
            return false;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getRawGamificationUserBadge($newId);
            if (is_array($row)) {
                $uid = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_user_badges',
                    $newId,
                    $uid,
                    'INSERT',
                    [],
                    $row
                );
            }
        }

        return true;
    }

    public function listBadgesByUser(int $userId): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT b.name, b.slug, b.icon, ub.awarded_at
             FROM adms_gamification_user_badges ub
             INNER JOIN adms_gamification_badges b ON b.id = ub.badge_id
             WHERE ub.user_id = :u
             ORDER BY ub.awarded_at DESC'
        );
        $stmt->execute([':u' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    public function listActiveWeeklyMissions(): array
    {
        $stmt = $this->getConnection()->query(
            'SELECT id, title, description, event_key, target_value, reward_points
             FROM adms_gamification_weekly_missions
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC'
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
        return is_array($rows) ? $rows : [];
    }

    /**
     * Missões para o dashboard de engajamento: para o mês filtrado, usa snapshot de progresso (qualquer utilizador)
     * quando existir, para alinhar texto/meta ao que valeu naquele mês; caso contrário, a definição actual na tabela.
     *
     * @param string $monthRef Formato Y-m
     *
     * @return array<int, array<string, mixed>>
     */
    public function listEngagementMissionsForMonth(string $monthRef): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $monthRef)) {
            $monthRef = date('Y-m');
        }
        $monthStart = $monthRef . '-01';
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT m.id,
                        COALESCE(
                            (SELECT p.snapshot_title FROM adms_gamification_user_mission_progress p
                             WHERE p.mission_id = m.id AND p.week_start_date = :w
                               AND NULLIF(TRIM(p.snapshot_title), \'\') IS NOT NULL
                             ORDER BY p.id ASC LIMIT 1),
                            m.title
                        ) AS title,
                        COALESCE(
                            (SELECT p.snapshot_description FROM adms_gamification_user_mission_progress p
                             WHERE p.mission_id = m.id AND p.week_start_date = :w2
                               AND NULLIF(TRIM(p.snapshot_description), \'\') IS NOT NULL
                             ORDER BY p.id ASC LIMIT 1),
                            m.description
                        ) AS description,
                        COALESCE(
                            (SELECT p.snapshot_target_value FROM adms_gamification_user_mission_progress p
                             WHERE p.mission_id = m.id AND p.week_start_date = :w3
                               AND p.snapshot_target_value IS NOT NULL
                             ORDER BY p.id ASC LIMIT 1),
                            m.target_value
                        ) AS target_value,
                        COALESCE(
                            (SELECT p.snapshot_reward_points FROM adms_gamification_user_mission_progress p
                             WHERE p.mission_id = m.id AND p.week_start_date = :w4
                               AND p.snapshot_reward_points IS NOT NULL
                             ORDER BY p.id ASC LIMIT 1),
                            m.reward_points
                        ) AS reward_points
                 FROM adms_gamification_weekly_missions m
                 WHERE m.is_active = 1
                 ORDER BY m.sort_order ASC, m.id ASC'
            );
            $stmt->execute([
                ':w' => $monthStart,
                ':w2' => $monthStart,
                ':w3' => $monthStart,
                ':w4' => $monthStart,
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return $this->listActiveWeeklyMissions();
        }

        return is_array($rows) ? $rows : [];
    }

    public function listWeeklyMissions(): array
    {
        $stmt = $this->getConnection()->query(
            'SELECT id, title, description, event_key, target_value, reward_points, sort_order, is_active
             FROM adms_gamification_weekly_missions
             ORDER BY sort_order ASC, id ASC'
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
        return is_array($rows) ? $rows : [];
    }

    /**
     * @param string $weekStartDate Primeiro dia do mês civil (Y-m-01); nome da coluna na BD mantém-se week_start_date.
     *
     * @return array{id:int,current_value:int,is_completed:int,effective_target:int,effective_reward:int}
     */
    public function upsertWeeklyMissionProgress(int $missionId, int $userId, string $weekStartDate, int $increment): array
    {
        $pdo = $this->getConnection();
        $increment = max(0, $increment);
        $sel = $pdo->prepare(
            'SELECT p.id, p.current_value, p.is_completed,
                    COALESCE(p.snapshot_target_value, m.target_value) AS eff_target,
                    COALESCE(p.snapshot_reward_points, m.reward_points) AS eff_reward
             FROM adms_gamification_user_mission_progress p
             INNER JOIN adms_gamification_weekly_missions m ON m.id = p.mission_id
             WHERE p.mission_id = :m AND p.user_id = :u AND p.week_start_date = :w
             LIMIT 1'
        );
        $sel->execute([':m' => $missionId, ':u' => $userId, ':w' => $weekStartDate]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $ins = $pdo->prepare(
                'INSERT INTO adms_gamification_user_mission_progress
                 (mission_id, user_id, week_start_date, current_value, is_completed,
                  snapshot_target_value, snapshot_reward_points, snapshot_title, snapshot_description,
                  created_at, updated_at)
                 SELECT :m, :u, :w, :v, 0,
                        m.target_value, m.reward_points, m.title, m.description,
                        NOW(), NOW()
                 FROM adms_gamification_weekly_missions m WHERE m.id = :m2 LIMIT 1'
            );
            $ins->execute([
                ':m' => $missionId,
                ':u' => $userId,
                ':w' => $weekStartDate,
                ':v' => $increment,
                ':m2' => $missionId,
            ]);
            if ((int)$pdo->lastInsertId() <= 0) {
                return ['id' => 0, 'current_value' => 0, 'is_completed' => 0, 'effective_target' => 1, 'effective_reward' => 0];
            }

            return $this->fetchMissionProgressState($missionId, $userId, $weekStartDate);
        }

        $newValue = (int)$row['current_value'] + $increment;
        $upd = $pdo->prepare(
            'UPDATE adms_gamification_user_mission_progress
             SET current_value = :v, updated_at = NOW()
             WHERE id = :id'
        );
        $upd->execute([':v' => $newValue, ':id' => (int)$row['id']]);

        return $this->fetchMissionProgressState($missionId, $userId, $weekStartDate);
    }

    /**
     * Preenche snapshots em linhas antigas (útil após cópias manuais na BD).
     *
     * @return int Linhas atualizadas
     */
    public function backfillNullMissionSnapshots(): int
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'UPDATE adms_gamification_user_mission_progress p
                 INNER JOIN adms_gamification_weekly_missions m ON m.id = p.mission_id
                 SET p.snapshot_target_value = m.target_value,
                     p.snapshot_reward_points = m.reward_points,
                     p.snapshot_title = m.title,
                     p.snapshot_description = m.description
                 WHERE p.snapshot_target_value IS NULL'
            );
            $stmt->execute();

            $n = $stmt->rowCount();
            if ($n > 0) {
                $uid = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_user_mission_progress',
                    0,
                    $uid,
                    'UPDATE',
                    ['backfill_snapshots_rows' => (string) $n],
                    ['backfill_snapshots_rows' => '0']
                );
            }

            return $n;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array{id:int,current_value:int,is_completed:int,effective_target:int,effective_reward:int}
     */
    private function fetchMissionProgressState(int $missionId, int $userId, string $weekStartDate): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT p.id, p.current_value, p.is_completed,
                    COALESCE(p.snapshot_target_value, m.target_value) AS eff_target,
                    COALESCE(p.snapshot_reward_points, m.reward_points) AS eff_reward
             FROM adms_gamification_user_mission_progress p
             INNER JOIN adms_gamification_weekly_missions m ON m.id = p.mission_id
             WHERE p.mission_id = :m AND p.user_id = :u AND p.week_start_date = :w
             LIMIT 1'
        );
        $stmt->execute([':m' => $missionId, ':u' => $userId, ':w' => $weekStartDate]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['id' => 0, 'current_value' => 0, 'is_completed' => 0, 'effective_target' => 1, 'effective_reward' => 0];
        }

        return [
            'id' => (int)$row['id'],
            'current_value' => (int)$row['current_value'],
            'is_completed' => (int)$row['is_completed'],
            'effective_target' => max(1, (int)$row['eff_target']),
            'effective_reward' => max(0, (int)$row['eff_reward']),
        ];
    }

    public function markMissionCompleted(int $progressId): void
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_user_mission_progress
             SET is_completed = 1, completed_at = NOW(), updated_at = NOW()
             WHERE id = :id AND is_completed = 0'
        );
        $stmt->execute([':id' => $progressId]);
    }

    /**
     * @param string $weekStartDate Y-m-01 (início do mês civil) para o qual se pede o progresso.
     */
    public function listWeeklyMissionProgressByUser(int $userId, string $weekStartDate): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT m.id AS mission_id,
                    COALESCE(p.snapshot_title, m.title) AS title,
                    COALESCE(p.snapshot_description, m.description) AS description,
                    COALESCE(p.snapshot_target_value, m.target_value) AS target_value,
                    COALESCE(p.snapshot_reward_points, m.reward_points) AS reward_points,
                    COALESCE(p.current_value, 0) AS current_value,
                    COALESCE(p.is_completed, 0) AS is_completed
             FROM adms_gamification_weekly_missions m
             LEFT JOIN adms_gamification_user_mission_progress p
               ON p.mission_id = m.id
              AND p.user_id = :u
              AND p.week_start_date = :w
             WHERE m.is_active = 1 OR p.id IS NOT NULL
             ORDER BY m.sort_order ASC, m.id ASC'
        );
        $stmt->execute([':u' => $userId, ':w' => $weekStartDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    public function registerAntiFraudEvent(
        int $userId,
        string $eventKey,
        string $refType,
        int $refId,
        string $reason,
        ?string $detailsJson = null
    ): void {
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_gamification_anti_fraud_events
             (user_id, event_key, ref_type, ref_id, reason, details_json, created_at)
             VALUES (:u, :e, :rt, :rid, :reason, :details, NOW())'
        );
        $stmt->execute([
            ':u' => $userId,
            ':e' => mb_substr(trim($eventKey), 0, 64),
            ':rt' => mb_substr(trim($refType), 0, 64),
            ':rid' => max(0, $refId),
            ':reason' => mb_substr(trim($reason), 0, 160),
            ':details' => $detailsJson,
        ]);
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getRawGamificationAntiFraudEvent($newId);
            if (is_array($row)) {
                $uid = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_anti_fraud_events',
                    $newId,
                    $uid,
                    'INSERT',
                    [],
                    $row
                );
            }
        }
    }

    public function getEngagementIndicators(string $monthRef): array
    {
        $exL = GamificationRankingExclusions::sqlLedgerUserNotExcluded('user_id');
        $stmt = $this->getConnection()->prepare(
            "SELECT
                (SELECT COUNT(DISTINCT user_id) FROM adms_gamification_point_ledger
                  WHERE DATE_FORMAT(created_at, \"%Y-%m\") = :m1 AND ({$exL})) AS active_users,
                (SELECT COUNT(*) FROM adms_gamification_anti_fraud_events WHERE DATE_FORMAT(created_at, \"%Y-%m\") = :m2) AS anti_fraud_blocks,
                (SELECT COALESCE(SUM(points),0) FROM adms_gamification_point_ledger
                  WHERE DATE_FORMAT(created_at, \"%Y-%m\") = :m3 AND ({$exL})) AS total_points_month"
        );
        $stmt->execute([':m1' => $monthRef, ':m2' => $monthRef, ':m3' => $monthRef]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : [];
    }

    public function getDepartmentEngagement(string $monthRef): array
    {
        $exU = GamificationRankingExclusions::sqlUserNameNotExcluded('u.name');
        $stmt = $this->getConnection()->prepare(
            "SELECT d.name AS department_name, COALESCE(SUM(l.points),0) AS total_points
             FROM adms_departments d
             LEFT JOIN adms_users u ON u.user_department_id = d.id AND u.status = 1 AND ({$exU})
             LEFT JOIN adms_gamification_point_ledger l ON l.user_id = u.id
               AND DATE_FORMAT(l.created_at, \"%Y-%m\") = :m
             GROUP BY d.id, d.name
             ORDER BY total_points DESC, d.name ASC
             LIMIT 10"
        );
        $stmt->execute([':m' => $monthRef]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    public function listRecentAntiFraudEvents(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->getConnection()->query(
            "SELECT a.id, a.event_key, a.reason, a.ref_type, a.ref_id, a.created_at, u.name AS user_name
             FROM adms_gamification_anti_fraud_events a
             INNER JOIN adms_users u ON u.id = a.user_id
             ORDER BY a.id DESC
             LIMIT {$limit}"
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
        return is_array($rows) ? $rows : [];
    }

    public function listSettings(): array
    {
        $stmt = $this->getConnection()->query(
            'SELECT setting_key, setting_value, description
             FROM adms_gamification_settings
             ORDER BY setting_key ASC'
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
        return is_array($rows) ? $rows : [];
    }

    public function getIntSetting(string $key, int $default): int
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT setting_value FROM adms_gamification_settings WHERE setting_key = :k LIMIT 1'
        );
        $stmt->execute([':k' => trim($key)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return $default;
        }
        return (int)($row['setting_value'] ?? $default);
    }

    public function updateSetting(string $key, string $value): bool
    {
        $key = trim($key);
        $oldRow = $this->getRawGamificationSettingByKey($key);
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_settings SET setting_value = :v, updated_at = NOW() WHERE setting_key = :k LIMIT 1'
        );
        $ok = $stmt->execute([':k' => $key, ':v' => trim($value)]);
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getRawGamificationSettingByKey($key);
            if (is_array($newRow)) {
                $uid = (int) ($_SESSION['user_id'] ?? 1);
                $oid = (int) ($newRow['id'] ?? $oldRow['id'] ?? 0);
                if ($oid > 0) {
                    LogAlteracaoService::registrarAlteracao(
                        'adms_gamification_settings',
                        $oid,
                        $uid,
                        'UPDATE',
                        $oldRow,
                        $newRow
                    );
                }
            }
        }

        return $ok;
    }

    public function settingExists(string $key): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT setting_key FROM adms_gamification_settings WHERE setting_key = :k LIMIT 1'
        );
        $stmt->execute([':k' => trim($key)]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createSetting(string $key, string $value, ?string $description = null): bool
    {
        $k = trim($key);
        if ($k === '') {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_gamification_settings (setting_key, setting_value, description, created_at, updated_at)
             VALUES (:k, :v, :d, NOW(), NOW())'
        );
        $desc = $description !== null ? trim($description) : '';
        $ok = $stmt->execute([
            ':k' => mb_substr($k, 0, 120),
            ':v' => trim($value),
            ':d' => $desc === '' ? null : mb_substr($desc, 0, 255),
        ]);
        if ($ok) {
            $row = $this->getRawGamificationSettingByKey($k);
            if (is_array($row) && (int) ($row['id'] ?? 0) > 0) {
                $uid = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_settings',
                    (int) $row['id'],
                    $uid,
                    'INSERT',
                    [],
                    $row
                );
            }
        }

        return $ok;
    }

    public function updateLevel(int $id, array $data): bool
    {
        $oldRow = $this->getRawGamificationLevel($id);
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_levels
             SET name = :name,
                 min_points = :min_points,
                 badge_color = :badge_color,
                 sort_order = :sort_order,
                 is_active = :is_active,
                 updated_at = NOW()
             WHERE id = :id
             LIMIT 1'
        );
        $ok = $stmt->execute([
            ':id' => $id,
            ':name' => mb_substr(trim((string)($data['name'] ?? '')), 0, 120),
            ':min_points' => max(0, (int)($data['min_points'] ?? 0)),
            ':badge_color' => mb_substr(trim((string)($data['badge_color'] ?? '')), 0, 20),
            ':sort_order' => max(0, (int)($data['sort_order'] ?? 0)),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getRawGamificationLevel($id);
            if (is_array($newRow)) {
                $uid = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_levels',
                    $id,
                    $uid,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    public function createLevel(array $data): bool
    {
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_gamification_levels
             (name, min_points, badge_color, sort_order, is_active, created_at, updated_at)
             VALUES (:name, :min_points, :badge_color, :sort_order, :is_active, NOW(), NOW())'
        );
        $ok = $stmt->execute([
            ':name' => mb_substr(trim((string)($data['name'] ?? '')), 0, 120),
            ':min_points' => max(0, (int)($data['min_points'] ?? 0)),
            ':badge_color' => mb_substr(trim((string)($data['badge_color'] ?? 'secondary')), 0, 20),
            ':sort_order' => max(0, (int)($data['sort_order'] ?? 0)),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        if ($ok) {
            $newId = (int) $this->getConnection()->lastInsertId();
            if ($newId > 0) {
                $row = $this->getRawGamificationLevel($newId);
                if (is_array($row)) {
                    $uid = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'adms_gamification_levels',
                        $newId,
                        $uid,
                        'INSERT',
                        [],
                        $row
                    );
                }
            }
        }

        return $ok;
    }

    public function deactivateLevel(int $id): bool
    {
        $oldRow = $this->getRawGamificationLevel($id);
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_levels SET is_active = 0, updated_at = NOW() WHERE id = :id LIMIT 1'
        );
        $ok = $stmt->execute([':id' => $id]);
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getRawGamificationLevel($id);
            if (is_array($newRow)) {
                $uid = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_levels',
                    $id,
                    $uid,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    public function updateBadge(int $id, array $data): bool
    {
        $oldRow = $this->getRawGamificationBadge($id);
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_badges
             SET name = :name,
                 slug = :slug,
                 description = :description,
                 criteria_key = :criteria_key,
                 criteria_value_json = :criteria_value_json,
                 icon = :icon,
                 is_active = :is_active,
                 updated_at = NOW()
             WHERE id = :id
             LIMIT 1'
        );
        $ok = $stmt->execute([
            ':id' => $id,
            ':name' => mb_substr(trim((string)($data['name'] ?? '')), 0, 120),
            ':slug' => mb_substr(trim((string)($data['slug'] ?? '')), 0, 140),
            ':description' => mb_substr(trim((string)($data['description'] ?? '')), 0, 255),
            ':criteria_key' => mb_substr(trim((string)($data['criteria_key'] ?? '')), 0, 64),
            ':criteria_value_json' => (string)($data['criteria_value_json'] ?? '{}'),
            ':icon' => mb_substr(trim((string)($data['icon'] ?? '')), 0, 80),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getRawGamificationBadge($id);
            if (is_array($newRow)) {
                $uid = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_badges',
                    $id,
                    $uid,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    public function createBadge(array $data): bool
    {
        $name = trim((string)($data['name'] ?? ''));
        $slug = trim((string)($data['slug'] ?? ''));
        if ($name === '' || $slug === '') {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_gamification_badges
             (name, slug, description, criteria_key, criteria_value_json, icon, is_active, created_at, updated_at)
             VALUES (:name, :slug, :description, :criteria_key, :criteria_value_json, :icon, :is_active, NOW(), NOW())'
        );
        $ok = $stmt->execute([
            ':name' => mb_substr($name, 0, 120),
            ':slug' => mb_substr($slug, 0, 140),
            ':description' => mb_substr(trim((string)($data['description'] ?? '')), 0, 255),
            ':criteria_key' => mb_substr(trim((string)($data['criteria_key'] ?? 'total_points')), 0, 64),
            ':criteria_value_json' => (string)($data['criteria_value_json'] ?? '{}'),
            ':icon' => mb_substr(trim((string)($data['icon'] ?? 'fa-award')), 0, 80),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        if ($ok) {
            $newId = (int) $this->getConnection()->lastInsertId();
            if ($newId > 0) {
                $row = $this->getRawGamificationBadge($newId);
                if (is_array($row)) {
                    $uid = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'adms_gamification_badges',
                        $newId,
                        $uid,
                        'INSERT',
                        [],
                        $row
                    );
                }
            }
        }

        return $ok;
    }

    public function deactivateBadge(int $id): bool
    {
        $oldRow = $this->getRawGamificationBadge($id);
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_badges SET is_active = 0, updated_at = NOW() WHERE id = :id LIMIT 1'
        );
        $ok = $stmt->execute([':id' => $id]);
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getRawGamificationBadge($id);
            if (is_array($newRow)) {
                $uid = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_badges',
                    $id,
                    $uid,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    public function updateMission(int $id, array $data): bool
    {
        $oldRow = $this->getRawGamificationWeeklyMission($id);
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_weekly_missions
             SET title = :title,
                 description = :description,
                 event_key = :event_key,
                 target_value = :target_value,
                 reward_points = :reward_points,
                 sort_order = :sort_order,
                 is_active = :is_active,
                 updated_at = NOW()
             WHERE id = :id
             LIMIT 1'
        );
        $ok = $stmt->execute([
            ':id' => $id,
            ':title' => mb_substr(trim((string)($data['title'] ?? '')), 0, 160),
            ':description' => mb_substr(trim((string)($data['description'] ?? '')), 0, 255),
            ':event_key' => mb_substr(trim((string)($data['event_key'] ?? '')), 0, 64),
            ':target_value' => max(1, (int)($data['target_value'] ?? 1)),
            ':reward_points' => max(0, (int)($data['reward_points'] ?? 0)),
            ':sort_order' => max(0, (int)($data['sort_order'] ?? 0)),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getRawGamificationWeeklyMission($id);
            if (is_array($newRow)) {
                $uid = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_weekly_missions',
                    $id,
                    $uid,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    public function createMission(array $data): bool
    {
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_gamification_weekly_missions
             (title, description, event_key, target_value, reward_points, sort_order, is_active, created_at, updated_at)
             VALUES (:title, :description, :event_key, :target_value, :reward_points, :sort_order, :is_active, NOW(), NOW())'
        );
        $ok = $stmt->execute([
            ':title' => mb_substr(trim((string)($data['title'] ?? '')), 0, 160),
            ':description' => mb_substr(trim((string)($data['description'] ?? '')), 0, 255),
            ':event_key' => mb_substr(trim((string)($data['event_key'] ?? 'timeline_comment_created')), 0, 64),
            ':target_value' => max(1, (int)($data['target_value'] ?? 1)),
            ':reward_points' => max(0, (int)($data['reward_points'] ?? 0)),
            ':sort_order' => max(0, (int)($data['sort_order'] ?? 0)),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        if ($ok) {
            $newId = (int) $this->getConnection()->lastInsertId();
            if ($newId > 0) {
                $row = $this->getRawGamificationWeeklyMission($newId);
                if (is_array($row)) {
                    $uid = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'adms_gamification_weekly_missions',
                        $newId,
                        $uid,
                        'INSERT',
                        [],
                        $row
                    );
                }
            }
        }

        return $ok;
    }

    public function deactivateMission(int $id): bool
    {
        $oldRow = $this->getRawGamificationWeeklyMission($id);
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_weekly_missions SET is_active = 0, updated_at = NOW() WHERE id = :id LIMIT 1'
        );
        $ok = $stmt->execute([':id' => $id]);
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getRawGamificationWeeklyMission($id);
            if (is_array($newRow)) {
                $uid = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_weekly_missions',
                    $id,
                    $uid,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRawGamificationSettingByKey(string $key): ?array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_gamification_settings WHERE setting_key = :k LIMIT 1'
        );
        $stmt->execute([':k' => trim($key)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRawGamificationLevel(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_gamification_levels WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRawGamificationBadge(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_gamification_badges WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRawGamificationWeeklyMission(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_gamification_weekly_missions WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRawGamificationUserBadge(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_gamification_user_badges WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRawGamificationAntiFraudEvent(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_gamification_anti_fraud_events WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }
}
