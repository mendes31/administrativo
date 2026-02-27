<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

class InvItemsRepository extends DbConnection
{
	public function getAll(int $page = 1, int $limit = 10, array $filters = []): array
	{
		$offset = max(0, ($page - 1) * $limit);
		$params = [];
		$wheres = [];

		if (!empty($filters['code'])) {
			$wheres[] = 'i.code LIKE :code';
			$params[':code'] = '%' . $filters['code'] . '%';
		}
		if (!empty($filters['description'])) {
			$wheres[] = 'i.description LIKE :description';
			$params[':description'] = '%' . $filters['description'] . '%';
		}
		if (isset($filters['active']) && $filters['active'] !== '') {
			$wheres[] = 'i.active = :active';
			$params[':active'] = (int)$filters['active'];
		}
		$whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';

        $sql = 'SELECT i.id, i.code, i.erp_code, i.description, i.admin_type, i.average_cost, i.min_stock, i.max_stock, i.active,
				u.name AS unit_name, c.name AS category_name,
				COALESCE(SUM(b.qty), 0) AS total_qty
			FROM inv_items i
			LEFT JOIN inv_units u ON u.id = i.inv_unit_id
			LEFT JOIN inv_categories c ON c.id = i.inv_category_id
			LEFT JOIN inv_balances b ON b.inv_item_id = i.id
			' . $whereSql . '
			GROUP BY i.id, i.code, i.description, i.admin_type, i.average_cost, i.min_stock, i.max_stock, i.active, u.name, c.name
			ORDER BY i.id DESC
			LIMIT :limit OFFSET :offset';

		$stmt = $this->getConnection()->prepare($sql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}

	public function countAll(array $filters = []): int
	{
		$params = [];
		$wheres = [];
		if (!empty($filters['code'])) {
			$wheres[] = 'code LIKE :code';
			$params[':code'] = '%' . $filters['code'] . '%';
		}
		if (!empty($filters['description'])) {
			$wheres[] = 'description LIKE :description';
			$params[':description'] = '%' . $filters['description'] . '%';
		}
		if (isset($filters['active']) && $filters['active'] !== '') {
			$wheres[] = 'active = :active';
			$params[':active'] = (int)$filters['active'];
		}
		$whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
		$sql = 'SELECT COUNT(*) AS total FROM inv_items ' . $whereSql;
		$stmt = $this->getConnection()->prepare($sql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return (int)($row['total'] ?? 0);
	}

	public function getOne(int $id): array|bool
	{
        $sql = 'SELECT i.*, u.name AS unit_name, c.name AS category_name
			FROM inv_items i
			LEFT JOIN inv_units u ON u.id = i.inv_unit_id
			LEFT JOIN inv_categories c ON c.id = i.inv_category_id
			WHERE i.id = :id LIMIT 1';
		$stmt = $this->getConnection()->prepare($sql);
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function create(array $data): int|bool
	{
		try {
            $sql = 'INSERT INTO inv_items (code, erp_code, description, inv_unit_id, inv_category_id, admin_type, average_cost, last_cost, min_stock, max_stock, active, created_at)
                VALUES (:code, :erp_code, :description, :inv_unit_id, :inv_category_id, :admin_type, :average_cost, :last_cost, :min_stock, :max_stock, :active, :created_at)';
			$stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':code', $data['code']);
            $stmt->bindValue(':erp_code', $data['erp_code'] ?? null, PDO::PARAM_STR);
			$stmt->bindValue(':description', $data['description']);
			$stmt->bindValue(':inv_unit_id', (int)$data['inv_unit_id'], PDO::PARAM_INT);
			$stmt->bindValue(':inv_category_id', !empty($data['inv_category_id']) ? (int)$data['inv_category_id'] : null, !empty($data['inv_category_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
			$stmt->bindValue(':admin_type', $data['admin_type']);
			$stmt->bindValue(':average_cost', $data['average_cost'] ?? 0);
			$stmt->bindValue(':last_cost', $data['last_cost'] ?? 0);
			$stmt->bindValue(':min_stock', $data['min_stock'] ?? 0);
			$stmt->bindValue(':max_stock', $data['max_stock'] ?? 0);
			$stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
			$stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
			$stmt->execute();
			return (int)$this->getConnection()->lastInsertId();
		} catch (Exception $e) {
			GenerateLog::generateLog('error', 'Falha ao criar item de estoque', ['error' => $e->getMessage(), 'code' => $data['code'] ?? '']);
			return false;
		}
	}

	public function update(int $id, array $data): bool
	{
		try {
            $sql = 'UPDATE inv_items SET code = :code, erp_code = :erp_code, description = :description, inv_unit_id = :inv_unit_id, inv_category_id = :inv_category_id,
				admin_type = :admin_type, average_cost = :average_cost, last_cost = :last_cost, min_stock = :min_stock, max_stock = :max_stock,
				active = :active, updated_at = :updated_at WHERE id = :id';
			$stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':code', $data['code']);
            $stmt->bindValue(':erp_code', $data['erp_code'] ?? null, PDO::PARAM_STR);
			$stmt->bindValue(':description', $data['description']);
			$stmt->bindValue(':inv_unit_id', (int)$data['inv_unit_id'], PDO::PARAM_INT);
			$stmt->bindValue(':inv_category_id', !empty($data['inv_category_id']) ? (int)$data['inv_category_id'] : null, !empty($data['inv_category_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
			$stmt->bindValue(':admin_type', $data['admin_type']);
			$stmt->bindValue(':average_cost', $data['average_cost'] ?? 0);
			$stmt->bindValue(':last_cost', $data['last_cost'] ?? 0);
			$stmt->bindValue(':min_stock', $data['min_stock'] ?? 0);
			$stmt->bindValue(':max_stock', $data['max_stock'] ?? 0);
			$stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
			$stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			return $stmt->execute();
		} catch (Exception $e) {
			GenerateLog::generateLog('error', 'Falha ao atualizar item de estoque', ['error' => $e->getMessage(), 'id' => $id]);
			return false;
		}
	}

	public function delete(int $id): bool
	{
		try {
			$sql = 'DELETE FROM inv_items WHERE id = :id';
			$stmt = $this->getConnection()->prepare($sql);
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			return $stmt->execute();
		} catch (Exception $e) {
			GenerateLog::generateLog('error', 'Falha ao excluir item de estoque', ['error' => $e->getMessage(), 'id' => $id]);
			return false;
		}
	}

	public function existsCode(string $code, ?int $excludeId = null): bool
	{
		$sql = 'SELECT id FROM inv_items WHERE code = :code' . ($excludeId ? ' AND id <> :id' : '') . ' LIMIT 1';
		$stmt = $this->getConnection()->prepare($sql);
		$stmt->bindValue(':code', $code);
		if ($excludeId) { $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT); }
		$stmt->execute();
		return (bool)$stmt->fetchColumn();
	}

	public function getAllForSelectWithAdminType(): array
	{
		$sql = 'SELECT id, code, description, admin_type FROM inv_items WHERE active = 1 ORDER BY description ASC';
		$stmt = $this->getConnection()->query($sql);
		return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}
}

