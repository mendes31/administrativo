<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
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
        return $stmt->execute([':u' => $userId, ':b' => $badgeId, ':meta' => $metaJson]) && $stmt->rowCount() > 0;
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

    public function upsertWeeklyMissionProgress(int $missionId, int $userId, string $weekStartDate, int $increment): array
    {
        $pdo = $this->getConnection();
        $sel = $pdo->prepare(
            'SELECT id, current_value, is_completed
             FROM adms_gamification_user_mission_progress
             WHERE mission_id = :m AND user_id = :u AND week_start_date = :w
             LIMIT 1'
        );
        $sel->execute([':m' => $missionId, ':u' => $userId, ':w' => $weekStartDate]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $ins = $pdo->prepare(
                'INSERT INTO adms_gamification_user_mission_progress
                 (mission_id, user_id, week_start_date, current_value, is_completed, created_at, updated_at)
                 VALUES (:m, :u, :w, :v, 0, NOW(), NOW())'
            );
            $ins->execute([':m' => $missionId, ':u' => $userId, ':w' => $weekStartDate, ':v' => max(0, $increment)]);
            $id = (int)$pdo->lastInsertId();
            return ['id' => $id, 'current_value' => max(0, $increment), 'is_completed' => 0];
        }

        $newValue = (int)$row['current_value'] + max(0, $increment);
        $upd = $pdo->prepare(
            'UPDATE adms_gamification_user_mission_progress
             SET current_value = :v, updated_at = NOW()
             WHERE id = :id'
        );
        $upd->execute([':v' => $newValue, ':id' => (int)$row['id']]);
        return ['id' => (int)$row['id'], 'current_value' => $newValue, 'is_completed' => (int)$row['is_completed']];
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

    public function listWeeklyMissionProgressByUser(int $userId, string $weekStartDate): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT m.id AS mission_id, m.title, m.description, m.target_value, m.reward_points,
                    COALESCE(p.current_value, 0) AS current_value,
                    COALESCE(p.is_completed, 0) AS is_completed
             FROM adms_gamification_weekly_missions m
             LEFT JOIN adms_gamification_user_mission_progress p
               ON p.mission_id = m.id
              AND p.user_id = :u
              AND p.week_start_date = :w
             WHERE m.is_active = 1
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
    }

    public function getEngagementIndicators(string $monthRef): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT
                (SELECT COUNT(DISTINCT user_id) FROM adms_gamification_point_ledger WHERE DATE_FORMAT(created_at, "%Y-%m") = :m1) AS active_users,
                (SELECT COUNT(*) FROM adms_gamification_anti_fraud_events WHERE DATE_FORMAT(created_at, "%Y-%m") = :m2) AS anti_fraud_blocks,
                (SELECT COALESCE(SUM(points),0) FROM adms_gamification_point_ledger WHERE DATE_FORMAT(created_at, "%Y-%m") = :m3) AS total_points_month'
        );
        $stmt->execute([':m1' => $monthRef, ':m2' => $monthRef, ':m3' => $monthRef]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : [];
    }

    public function getDepartmentEngagement(string $monthRef): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT d.name AS department_name, COALESCE(SUM(l.points),0) AS total_points
             FROM adms_departments d
             LEFT JOIN adms_users u ON u.user_department_id = d.id AND u.status = 1
             LEFT JOIN adms_gamification_point_ledger l ON l.user_id = u.id
               AND DATE_FORMAT(l.created_at, "%Y-%m") = :m
             GROUP BY d.id, d.name
             ORDER BY total_points DESC, d.name ASC
             LIMIT 10'
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
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_settings SET setting_value = :v, updated_at = NOW() WHERE setting_key = :k LIMIT 1'
        );
        return $stmt->execute([':k' => trim($key), ':v' => trim($value)]);
    }

    public function updateLevel(int $id, array $data): bool
    {
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
        return $stmt->execute([
            ':id' => $id,
            ':name' => mb_substr(trim((string)($data['name'] ?? '')), 0, 120),
            ':min_points' => max(0, (int)($data['min_points'] ?? 0)),
            ':badge_color' => mb_substr(trim((string)($data['badge_color'] ?? '')), 0, 20),
            ':sort_order' => max(0, (int)($data['sort_order'] ?? 0)),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
    }

    public function createLevel(array $data): bool
    {
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_gamification_levels
             (name, min_points, badge_color, sort_order, is_active, created_at, updated_at)
             VALUES (:name, :min_points, :badge_color, :sort_order, :is_active, NOW(), NOW())'
        );
        return $stmt->execute([
            ':name' => mb_substr(trim((string)($data['name'] ?? '')), 0, 120),
            ':min_points' => max(0, (int)($data['min_points'] ?? 0)),
            ':badge_color' => mb_substr(trim((string)($data['badge_color'] ?? 'secondary')), 0, 20),
            ':sort_order' => max(0, (int)($data['sort_order'] ?? 0)),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
    }

    public function deactivateLevel(int $id): bool
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_levels SET is_active = 0, updated_at = NOW() WHERE id = :id LIMIT 1'
        );
        return $stmt->execute([':id' => $id]);
    }

    public function updateBadge(int $id, array $data): bool
    {
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
        return $stmt->execute([
            ':id' => $id,
            ':name' => mb_substr(trim((string)($data['name'] ?? '')), 0, 120),
            ':slug' => mb_substr(trim((string)($data['slug'] ?? '')), 0, 140),
            ':description' => mb_substr(trim((string)($data['description'] ?? '')), 0, 255),
            ':criteria_key' => mb_substr(trim((string)($data['criteria_key'] ?? '')), 0, 64),
            ':criteria_value_json' => (string)($data['criteria_value_json'] ?? '{}'),
            ':icon' => mb_substr(trim((string)($data['icon'] ?? '')), 0, 80),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
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
        return $stmt->execute([
            ':name' => mb_substr($name, 0, 120),
            ':slug' => mb_substr($slug, 0, 140),
            ':description' => mb_substr(trim((string)($data['description'] ?? '')), 0, 255),
            ':criteria_key' => mb_substr(trim((string)($data['criteria_key'] ?? 'total_points')), 0, 64),
            ':criteria_value_json' => (string)($data['criteria_value_json'] ?? '{}'),
            ':icon' => mb_substr(trim((string)($data['icon'] ?? 'fa-award')), 0, 80),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
    }

    public function deactivateBadge(int $id): bool
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_badges SET is_active = 0, updated_at = NOW() WHERE id = :id LIMIT 1'
        );
        return $stmt->execute([':id' => $id]);
    }

    public function updateMission(int $id, array $data): bool
    {
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
        return $stmt->execute([
            ':id' => $id,
            ':title' => mb_substr(trim((string)($data['title'] ?? '')), 0, 160),
            ':description' => mb_substr(trim((string)($data['description'] ?? '')), 0, 255),
            ':event_key' => mb_substr(trim((string)($data['event_key'] ?? '')), 0, 64),
            ':target_value' => max(1, (int)($data['target_value'] ?? 1)),
            ':reward_points' => max(0, (int)($data['reward_points'] ?? 0)),
            ':sort_order' => max(0, (int)($data['sort_order'] ?? 0)),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
    }

    public function createMission(array $data): bool
    {
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_gamification_weekly_missions
             (title, description, event_key, target_value, reward_points, sort_order, is_active, created_at, updated_at)
             VALUES (:title, :description, :event_key, :target_value, :reward_points, :sort_order, :is_active, NOW(), NOW())'
        );
        return $stmt->execute([
            ':title' => mb_substr(trim((string)($data['title'] ?? '')), 0, 160),
            ':description' => mb_substr(trim((string)($data['description'] ?? '')), 0, 255),
            ':event_key' => mb_substr(trim((string)($data['event_key'] ?? 'timeline_comment_created')), 0, 64),
            ':target_value' => max(1, (int)($data['target_value'] ?? 1)),
            ':reward_points' => max(0, (int)($data['reward_points'] ?? 0)),
            ':sort_order' => max(0, (int)($data['sort_order'] ?? 0)),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
    }

    public function deactivateMission(int $id): bool
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_weekly_missions SET is_active = 0, updated_at = NOW() WHERE id = :id LIMIT 1'
        );
        return $stmt->execute([':id' => $id]);
    }
}
