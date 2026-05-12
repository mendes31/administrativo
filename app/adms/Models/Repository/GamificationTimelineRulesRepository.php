<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class GamificationTimelineRulesRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        $sql = 'SELECT id, event_key, title, description, points, max_awards_per_user_per_day, max_awards_per_user_total, is_active, created_at, updated_at
                FROM adms_gamification_timeline_rules
                ORDER BY event_key ASC';
        $stmt = $this->getConnection()->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;

        return is_array($rows) ? $rows : [];
    }

    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT id, event_key, title, description, points, max_awards_per_user_per_day, max_awards_per_user_total, is_active, created_at, updated_at
             FROM adms_gamification_timeline_rules WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findByEventKey(string $eventKey): ?array
    {
        $eventKey = trim($eventKey);
        if ($eventKey === '') {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT id, event_key, title, description, points, max_awards_per_user_per_day, max_awards_per_user_total, is_active, created_at, updated_at
             FROM adms_gamification_timeline_rules WHERE event_key = :k LIMIT 1'
        );
        $stmt->execute([':k' => $eventKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @param array{points?:int,max_awards_per_user_per_day?:int|null,max_awards_per_user_total?:int|null,is_active?:bool,title?:string,description?:string|null} $data
     */
    public function updateById(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }
        $oldRow = $this->findById($id);
        $points = isset($data['points']) ? max(0, min(999999, (int)$data['points'])) : null;
        $title = isset($data['title']) ? trim((string)$data['title']) : null;
        $description = array_key_exists('description', $data) ? ($data['description'] !== null ? trim((string)$data['description']) : null) : null;
        $isActive = array_key_exists('is_active', $data) ? (bool)$data['is_active'] : null;
        $maxDay = array_key_exists('max_awards_per_user_per_day', $data)
            ? ($data['max_awards_per_user_per_day'] === null ? null : max(0, min(999999, (int)$data['max_awards_per_user_per_day'])))
            : null;
        $maxTot = array_key_exists('max_awards_per_user_total', $data)
            ? ($data['max_awards_per_user_total'] === null ? null : max(0, min(999999, (int)$data['max_awards_per_user_total'])))
            : null;

        $fields = [];
        $params = [':id' => $id];
        if ($points !== null) {
            $fields[] = 'points = :points';
            $params[':points'] = $points;
        }
        if ($title !== null && $title !== '') {
            $fields[] = 'title = :title';
            $params[':title'] = mb_substr($title, 0, 191);
        }
        if ($description !== null) {
            $fields[] = 'description = :description';
            $params[':description'] = $description === '' ? null : $description;
        }
        if ($isActive !== null) {
            $fields[] = 'is_active = :is_active';
            $params[':is_active'] = $isActive ? 1 : 0;
        }
        if (array_key_exists('max_awards_per_user_per_day', $data)) {
            $fields[] = 'max_awards_per_user_per_day = :max_day';
            $params[':max_day'] = $maxDay;
        }
        if (array_key_exists('max_awards_per_user_total', $data)) {
            $fields[] = 'max_awards_per_user_total = :max_tot';
            $params[':max_tot'] = $maxTot;
        }
        if ($fields === []) {
            return false;
        }
        $fields[] = 'updated_at = NOW()';
        $sql = 'UPDATE adms_gamification_timeline_rules SET ' . implode(', ', $fields) . ' WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $ok = $stmt->execute($params);
        if ($ok) {
            $newRow = $this->findById($id);
            if (is_array($oldRow) && is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_timeline_rules',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    /**
     * @param array{event_key:string,title:string,description?:string|null,points?:int,max_awards_per_user_per_day?:int|null,max_awards_per_user_total?:int|null,is_active?:bool} $data
     */
    public function create(array $data): bool
    {
        $eventKey = trim((string)($data['event_key'] ?? ''));
        $title = trim((string)($data['title'] ?? ''));
        if ($eventKey === '' || $title === '') {
            return false;
        }

        $points = max(0, min(999999, (int)($data['points'] ?? 0)));
        $description = array_key_exists('description', $data) ? (string)$data['description'] : '';
        $description = trim($description);
        $maxDay = array_key_exists('max_awards_per_user_per_day', $data)
            ? ($data['max_awards_per_user_per_day'] === null ? null : max(0, min(999999, (int)$data['max_awards_per_user_per_day'])))
            : null;
        $maxTotal = array_key_exists('max_awards_per_user_total', $data)
            ? ($data['max_awards_per_user_total'] === null ? null : max(0, min(999999, (int)$data['max_awards_per_user_total'])))
            : null;
        $isActive = array_key_exists('is_active', $data) ? (!empty($data['is_active']) ? 1 : 0) : 1;

        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_gamification_timeline_rules
             (event_key, title, description, points, max_awards_per_user_per_day, max_awards_per_user_total, is_active, created_at, updated_at)
             VALUES (:event_key, :title, :description, :points, :max_day, :max_total, :is_active, NOW(), NOW())'
        );

        $ok = $stmt->execute([
            ':event_key' => mb_substr($eventKey, 0, 120),
            ':title' => mb_substr($title, 0, 191),
            ':description' => $description === '' ? null : mb_substr($description, 0, 255),
            ':points' => $points,
            ':max_day' => $maxDay,
            ':max_total' => $maxTotal,
            ':is_active' => $isActive,
        ]);
        if ($ok) {
            $newId = (int) $this->getConnection()->lastInsertId();
            if ($newId > 0) {
                $row = $this->findById($newId);
                if (is_array($row)) {
                    $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'adms_gamification_timeline_rules',
                        $newId,
                        $usuarioId,
                        'INSERT',
                        [],
                        $row
                    );
                }
            }
        }

        return $ok;
    }
}
