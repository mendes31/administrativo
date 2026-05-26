<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\TextEncodingHelper;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SacCategoriesRepository extends DbConnection
{
    public function getAllCategories(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $whereConditions = [];
        $params = [];

        if (!empty($filters['name'])) {
            $whereConditions[] = 'c.name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $whereConditions[] = 'c.is_active = :is_active';
            $params[':is_active'] = (int)$filters['is_active'];
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT c.*
                FROM sac_categories c
                {$whereClause}
                ORDER BY c.display_order ASC, c.name ASC
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

    public function getTotalCategories(array $filters = []): int
    {
        $whereConditions = [];
        $params = [];

        if (!empty($filters['name'])) {
            $whereConditions[] = 'name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $whereConditions[] = 'is_active = :is_active';
            $params[':is_active'] = (int)$filters['is_active'];
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT COUNT(*) as total FROM sac_categories {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['total'];
    }

    public function getCategoryById(int $id): ?array
    {
        $sql = "SELECT * FROM sac_categories WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->normalizeRow($result) : null;
    }

    public function getActiveCategories(): array
    {
        $sql = "SELECT id, name, color, icon, display_order
                FROM sac_categories
                WHERE is_active = 1
                ORDER BY display_order ASC, name ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function createCategory(array $data): int|false
    {
        $sql = "INSERT INTO sac_categories (name, description, color, icon, default_sla_response_hours, default_sla_resolution_hours, is_active, display_order, created_at, updated_at)
                VALUES (:name, :description, :color, :icon, :default_sla_response_hours, :default_sla_resolution_hours, :is_active, :display_order, NOW(), NOW())";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':color', $data['color'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':icon', $data['icon'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':default_sla_response_hours', $data['default_sla_response_hours'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':default_sla_resolution_hours', $data['default_sla_resolution_hours'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':is_active', $data['is_active'] ?? 1, PDO::PARAM_INT);
        $stmt->bindValue(':display_order', $data['display_order'] ?? 0, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return false;
        }

        $newId = (int)$this->getConnection()->lastInsertId();

        if ($newId > 0) {
            $newData = $this->getCategoryById($newId);
            if (is_array($newData)) {
                $usuarioId = (int)($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'sac_categories',
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

    public function updateCategory(int $id, array $data): bool
    {
        $oldData = $this->getCategoryById($id);

        $sql = "UPDATE sac_categories
                SET name = :name, description = :description, color = :color, icon = :icon,
                    default_sla_response_hours = :default_sla_response_hours, default_sla_resolution_hours = :default_sla_resolution_hours,
                    is_active = :is_active, display_order = :display_order, updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':color', $data['color'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':icon', $data['icon'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':default_sla_response_hours', $data['default_sla_response_hours'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':default_sla_resolution_hours', $data['default_sla_resolution_hours'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':is_active', $data['is_active'] ?? 1, PDO::PARAM_INT);
        $stmt->bindValue(':display_order', $data['display_order'] ?? 0, PDO::PARAM_INT);

        $ok = $stmt->execute();

        if ($ok && $stmt->rowCount() > 0 && is_array($oldData)) {
            $newData = $this->getCategoryById($id);
            if (is_array($newData)) {
                $usuarioId = (int)($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'sac_categories',
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

    public function deleteCategory(int $id): bool
    {
        $oldData = $this->getCategoryById($id);

        $sql = "DELETE FROM sac_categories WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;

        if ($deleted && is_array($oldData)) {
            $usuarioId = (int)($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'sac_categories',
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
        foreach (['name', 'description'] as $field) {
            if (array_key_exists($field, $row) && is_string($row[$field])) {
                $row[$field] = TextEncodingHelper::decodeEntities($row[$field]);
            }
        }
        return $row;
    }
}
