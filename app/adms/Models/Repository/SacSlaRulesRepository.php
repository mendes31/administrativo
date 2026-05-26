<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\TextEncodingHelper;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SacSlaRulesRepository extends DbConnection
{
    public function getAllRules(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $whereConditions = [];
        $params = [];

        if (!empty($filters['name'])) {
            $whereConditions[] = 'r.name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }

        if (!empty($filters['priority'])) {
            $whereConditions[] = 'r.priority = :priority';
            $params[':priority'] = $filters['priority'];
        }

        if (!empty($filters['category_id'])) {
            $whereConditions[] = 'r.category_id = :category_id';
            $params[':category_id'] = (int)$filters['category_id'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $whereConditions[] = 'r.is_active = :is_active';
            $params[':is_active'] = (int)$filters['is_active'];
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT r.*, c.name as category_name
                FROM sac_sla_rules r
                LEFT JOIN sac_categories c ON r.category_id = c.id
                {$whereClause}
                ORDER BY r.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function getTotalRules(array $filters = []): int
    {
        $whereConditions = [];
        $params = [];

        if (!empty($filters['name'])) {
            $whereConditions[] = 'r.name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }

        if (!empty($filters['priority'])) {
            $whereConditions[] = 'r.priority = :priority';
            $params[':priority'] = $filters['priority'];
        }

        if (!empty($filters['category_id'])) {
            $whereConditions[] = 'r.category_id = :category_id';
            $params[':category_id'] = (int)$filters['category_id'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $whereConditions[] = 'r.is_active = :is_active';
            $params[':is_active'] = (int)$filters['is_active'];
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT COUNT(*) as total FROM sac_sla_rules r {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['total'];
    }

    public function getRuleById(int $id): ?array
    {
        $sql = "SELECT r.*, c.name as category_name
                FROM sac_sla_rules r
                LEFT JOIN sac_categories c ON r.category_id = c.id
                WHERE r.id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->normalizeRow($result) : null;
    }

    public function getActiveRules(): array
    {
        $sql = "SELECT r.*, c.name as category_name
                FROM sac_sla_rules r
                LEFT JOIN sac_categories c ON r.category_id = c.id
                WHERE r.is_active = 1
                ORDER BY r.priority ASC, r.name ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    /**
     * Busca a regra SLA mais específica para um ticket.
     * Prioridade: categoria+prioridade > categoria > prioridade > genérica
     */
    public function findRuleForTicket(int $categoryId, string $priority): ?array
    {
        $sql = "SELECT r.*, c.name as category_name
                FROM sac_sla_rules r
                LEFT JOIN sac_categories c ON r.category_id = c.id
                WHERE r.is_active = 1
                  AND (
                    (r.category_id = :cat1 AND r.priority = :pri1)
                    OR (r.category_id = :cat2 AND r.priority IS NULL)
                    OR (r.category_id IS NULL AND r.priority = :pri2)
                    OR (r.category_id IS NULL AND r.priority IS NULL)
                  )
                ORDER BY
                    CASE
                        WHEN r.category_id IS NOT NULL AND r.priority IS NOT NULL THEN 1
                        WHEN r.category_id IS NOT NULL AND r.priority IS NULL THEN 2
                        WHEN r.category_id IS NULL AND r.priority IS NOT NULL THEN 3
                        ELSE 4
                    END ASC
                LIMIT 1";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':cat1', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':cat2', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':pri1', $priority, PDO::PARAM_STR);
        $stmt->bindValue(':pri2', $priority, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->normalizeRow($result) : null;
    }

    public function createRule(array $data): int|false
    {
        $sql = "INSERT INTO sac_sla_rules (name, category_id, priority, response_time_hours, resolution_time_hours, escalation_enabled, escalation_after_hours, escalation_user_id, is_active, created_at, updated_at)
                VALUES (:name, :category_id, :priority, :response_time_hours, :resolution_time_hours, :escalation_enabled, :escalation_after_hours, :escalation_user_id, :is_active, NOW(), NOW())";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':category_id', $data['category_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':priority', $data['priority'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':response_time_hours', (int)$data['response_time_hours'], PDO::PARAM_INT);
        $stmt->bindValue(':resolution_time_hours', (int)$data['resolution_time_hours'], PDO::PARAM_INT);
        $stmt->bindValue(':escalation_enabled', $data['escalation_enabled'] ?? 0, PDO::PARAM_INT);
        $stmt->bindValue(':escalation_after_hours', $data['escalation_after_hours'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':escalation_user_id', $data['escalation_user_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':is_active', $data['is_active'] ?? 1, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return false;
        }

        $newId = (int)$this->getConnection()->lastInsertId();

        if ($newId > 0) {
            $newData = $this->getRuleById($newId);
            if (is_array($newData)) {
                $usuarioId = (int)($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'sac_sla_rules',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $newData
                );
            }
        }

        return $newId;
    }

    public function updateRule(int $id, array $data): bool
    {
        $oldData = $this->getRuleById($id);

        $sql = "UPDATE sac_sla_rules
                SET name = :name, category_id = :category_id, priority = :priority,
                    response_time_hours = :response_time_hours, resolution_time_hours = :resolution_time_hours,
                    escalation_enabled = :escalation_enabled, escalation_after_hours = :escalation_after_hours,
                    escalation_user_id = :escalation_user_id, is_active = :is_active, updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':category_id', $data['category_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':priority', $data['priority'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':response_time_hours', (int)$data['response_time_hours'], PDO::PARAM_INT);
        $stmt->bindValue(':resolution_time_hours', (int)$data['resolution_time_hours'], PDO::PARAM_INT);
        $stmt->bindValue(':escalation_enabled', $data['escalation_enabled'] ?? 0, PDO::PARAM_INT);
        $stmt->bindValue(':escalation_after_hours', $data['escalation_after_hours'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':escalation_user_id', $data['escalation_user_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':is_active', $data['is_active'] ?? 1, PDO::PARAM_INT);

        $ok = $stmt->execute();

        if ($ok && $stmt->rowCount() > 0 && is_array($oldData)) {
            $newData = $this->getRuleById($id);
            if (is_array($newData)) {
                $usuarioId = (int)($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'sac_sla_rules',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            }
        }

        return $ok;
    }

    public function deleteRule(int $id): bool
    {
        $oldData = $this->getRuleById($id);

        $sql = "DELETE FROM sac_sla_rules WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;

        if ($deleted && is_array($oldData)) {
            $usuarioId = (int)($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'sac_sla_rules',
                $id,
                $usuarioId,
                'DELETE',
                $oldData,
                []
            );
        }

        return $deleted;
    }

    private function normalizeRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (is_array($row)) {
                $row = $this->normalizeRow($row);
            }
        }
        unset($row);
        return $rows;
    }

    private function normalizeRow(array $row): array
    {
        foreach (['name', 'description', 'category_name'] as $field) {
            if (array_key_exists($field, $row) && is_string($row[$field])) {
                $row[$field] = TextEncodingHelper::decodeEntities($row[$field]);
            }
        }
        return $row;
    }
}
