<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class InvCategoriesRepository extends DbConnection
{
    public function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $params = [];
        $wheres = [];
        if (!empty($filters['name'])) { $wheres[] = 'name LIKE :name'; $params[':name'] = '%' . $filters['name'] . '%'; }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $sql = 'SELECT id, name FROM inv_categories ' . $whereSql . ' ORDER BY name ASC LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countAll(array $filters = []): int
    {
        $params = [];
        $wheres = [];
        if (!empty($filters['name'])) { $wheres[] = 'name LIKE :name'; $params[':name'] = '%' . $filters['name'] . '%'; }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) FROM inv_categories ' . $whereSql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getOne(int $id): array|bool
    {
        $stmt = $this->getConnection()->prepare('SELECT id, name FROM inv_categories WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int|bool
    {
        $stmt = $this->getConnection()->prepare('INSERT INTO inv_categories (name, created_at) VALUES (:name, NOW())');
        $stmt->bindValue(':name', $data['name']);
        if ($stmt->execute()) {
            $newId = (int) $this->getConnection()->lastInsertId();
            if ($newId > 0) {
                $row = $this->getRowById($newId);
                if (is_array($row)) {
                    $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'inv_categories',
                        $newId,
                        $usuarioId,
                        'INSERT',
                        [],
                        $row
                    );
                }
            }

            return $newId;
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $oldRow = $this->getRowById($id);
        $stmt = $this->getConnection()->prepare('UPDATE inv_categories SET name = :name, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getRowById($id);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'inv_categories',
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

    public function delete(int $id): bool
    {
        $oldRow = $this->getRowById($id);
        $stmt = $this->getConnection()->prepare('DELETE FROM inv_categories WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'inv_categories',
                $id,
                $usuarioId,
                'DELETE',
                $oldRow,
                []
            );
        }

        return $ok;
    }
    public function getAllForSelect(): array
    {
        $sql = 'SELECT id, name FROM inv_categories ORDER BY name';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Grupos (OITB) presentes em itens sincronizados do SAP — para filtro da listagem.
     *
     * @return list<array{id: int|string, name: string}>
     */
    public function getImportedForSelect(): array
    {
        $sql = 'SELECT DISTINCT c.id, c.name
                FROM inv_categories c
                INNER JOIN inv_items i ON i.inv_category_id = c.id
                WHERE i.erp_code IS NOT NULL AND TRIM(i.erp_code) <> \'\'
                ORDER BY c.name ASC';
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findIdByName(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT id FROM inv_categories WHERE name = :name LIMIT 1');
        $stmt->bindValue(':name', $name);
        $stmt->execute();
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    public function findOrCreateByName(string $name): ?int
    {
        $id = $this->findIdByName($name);
        if ($id !== null) {
            return $id;
        }

        $newId = $this->create(['name' => trim($name)]);
        return is_int($newId) && $newId > 0 ? $newId : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRowById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare('SELECT * FROM inv_categories WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }
}



