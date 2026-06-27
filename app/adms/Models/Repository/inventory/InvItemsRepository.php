<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\InvCostProjectHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
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
		if (!empty($filters['categoria_id'])) {
			$wheres[] = 'i.inv_category_id = :categoria_id';
			$params[':categoria_id'] = (int)$filters['categoria_id'];
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
			ORDER BY i.code ASC
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
		if (!empty($filters['categoria_id'])) {
			$wheres[] = 'inv_category_id = :categoria_id';
			$params[':categoria_id'] = (int)$filters['categoria_id'];
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
            $sql = 'INSERT INTO inv_items (code, erp_code, description, inv_unit_id, inv_category_id, admin_type, average_cost, last_cost, min_stock, max_stock, standard_batch_size, energy_class, complexity_level, production_line, active, created_at)
                VALUES (:code, :erp_code, :description, :inv_unit_id, :inv_category_id, :admin_type, :average_cost, :last_cost, :min_stock, :max_stock, :standard_batch_size, :energy_class, :complexity_level, :production_line, :active, :created_at)';
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
			$stmt->bindValue(':standard_batch_size', max(0.000001, (float)($data['standard_batch_size'] ?? 1)));
			$stmt->bindValue(':energy_class', $this->nullableString($data['energy_class'] ?? null), $this->nullableString($data['energy_class'] ?? null) !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
			$stmt->bindValue(':complexity_level', $this->normalizeComplexityLevel($data['complexity_level'] ?? 'media'));
			$stmt->bindValue(':production_line', $this->nullableString($data['production_line'] ?? null), $this->nullableString($data['production_line'] ?? null) !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
			$stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
			$stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
			$stmt->execute();
			$newId = (int) $this->getConnection()->lastInsertId();
			if ($newId > 0) {
				$row = $this->getItemRowById($newId);
				if (is_array($row)) {
					$usuarioId = (int) ($_SESSION['user_id'] ?? 1);
					LogAlteracaoService::registrarAlteracao(
						'inv_items',
						$newId,
						$usuarioId,
						'INSERT',
						[],
						$row
					);
				}
			}

			return $newId;
		} catch (Exception $e) {
			GenerateLog::generateLog('error', 'Falha ao criar item de estoque', ['error' => $e->getMessage(), 'code' => $data['code'] ?? '']);
			return false;
		}
	}

	public function update(int $id, array $data): bool
	{
		try {
			$oldRow = $this->getItemRowById($id);
            $sql = 'UPDATE inv_items SET code = :code, erp_code = :erp_code, description = :description, inv_unit_id = :inv_unit_id, inv_category_id = :inv_category_id,
				admin_type = :admin_type, average_cost = :average_cost, last_cost = :last_cost, min_stock = :min_stock, max_stock = :max_stock,
				standard_batch_size = :standard_batch_size, energy_class = :energy_class, complexity_level = :complexity_level,
                production_line = :production_line, active = :active, updated_at = :updated_at WHERE id = :id';
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
			$stmt->bindValue(':standard_batch_size', max(0.000001, (float)($data['standard_batch_size'] ?? ($oldRow['standard_batch_size'] ?? 1))));
			$stmt->bindValue(':energy_class', $this->nullableString($data['energy_class'] ?? null), $this->nullableString($data['energy_class'] ?? null) !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
			$stmt->bindValue(':complexity_level', $this->normalizeComplexityLevel($data['complexity_level'] ?? ($oldRow['complexity_level'] ?? 'media')));
			$stmt->bindValue(':production_line', $this->nullableString($data['production_line'] ?? null), $this->nullableString($data['production_line'] ?? null) !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
			$stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
			$stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			$ok = $stmt->execute();
			if ($ok && is_array($oldRow)) {
				$newRow = $this->getItemRowById($id);
				if (is_array($newRow)) {
					$usuarioId = (int) ($_SESSION['user_id'] ?? 1);
					LogAlteracaoService::registrarAlteracao(
						'inv_items',
						$id,
						$usuarioId,
						'UPDATE',
						$oldRow,
						$newRow
					);
				}
			}

			return $ok;
		} catch (Exception $e) {
			GenerateLog::generateLog('error', 'Falha ao atualizar item de estoque', ['error' => $e->getMessage(), 'id' => $id]);
			return false;
		}
	}

	public function delete(int $id): bool
	{
		try {
			$oldRow = $this->getItemRowById($id);
			$sql = 'DELETE FROM inv_items WHERE id = :id';
			$stmt = $this->getConnection()->prepare($sql);
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			$ok = $stmt->execute();
			if ($ok && is_array($oldRow)) {
				$usuarioId = (int) ($_SESSION['user_id'] ?? 1);
				LogAlteracaoService::registrarAlteracao(
					'inv_items',
					$id,
					$usuarioId,
					'DELETE',
					$oldRow,
					[]
				);
			}

			return $ok;
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
		$sql = 'SELECT i.id, i.code, i.description, i.admin_type, i.average_cost, u.name AS unit_name
				FROM inv_items i
				LEFT JOIN inv_units u ON u.id = i.inv_unit_id
				WHERE i.active = 1
				ORDER BY i.description ASC';
		$stmt = $this->getConnection()->query($sql);
		return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}

	/**
	 * Itens PA/PI para simulação de custo (categorias com ACABADO ou INTERMED).
	 */
	public function getFinishedGoodsForSelect(): array
	{
		$sql = 'SELECT i.id, i.code, i.description, i.erp_code, i.average_cost, c.name AS category_name
				FROM inv_items i
				LEFT JOIN inv_categories c ON c.id = i.inv_category_id
				WHERE i.active = 1
				  AND (
				    UPPER(c.name) LIKE :pa OR UPPER(c.name) LIKE :pi
				    OR UPPER(c.name) LIKE :acabado OR UPPER(c.name) LIKE :intermed
				  )
				ORDER BY i.description ASC';
		$stmt = $this->getConnection()->prepare($sql);
		$stmt->bindValue(':pa', '%ACABADO%');
		$stmt->bindValue(':pi', '%INTERMED%');
		$stmt->bindValue(':acabado', '%PROD ACAB%');
		$stmt->bindValue(':intermed', '%PROD INTER%');
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}

	/**
	 * PAs em fase de projeto (simulação de custeio / nova coluna na planilha).
	 *
	 * @return list<array<string, mixed>>
	 */
	public function getProjectItemsForSelect(): array
	{
		$sql = "SELECT i.id, i.code, i.description, i.erp_code, c.name AS category_name
				FROM inv_items i
				LEFT JOIN inv_categories c ON c.id = i.inv_category_id
				WHERE i.active = 1
				  AND UPPER(TRIM(c.name)) = 'PA - PROJETO'
				ORDER BY i.description ASC";
		$stmt = $this->getConnection()->query($sql);

		return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}

	public function findByErpCode(string $erpCode): ?array
	{
		$erpCode = trim($erpCode);
		if ($erpCode === '') {
			return null;
		}
		$stmt = $this->getConnection()->prepare('SELECT * FROM inv_items WHERE erp_code = :erp_code LIMIT 1');
		$stmt->bindValue(':erp_code', $erpCode);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row !== false ? $row : null;
	}

	/**
	 * Carrega metadados de itens por id e/ou erp_code (uma query).
	 *
	 * @param list<int> $ids
	 * @param list<string> $erpCodes
	 * @return array{by_id: array<int, array<string, mixed>>, by_erp: array<string, array<string, mixed>>}
	 */
	public function getDetailedMapForProduction(array $ids, array $erpCodes): array
	{
		$byId = [];
		$byErp = [];
		$idList = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $v): bool => $v > 0)));
		$erpList = array_values(array_unique(array_filter(array_map(
			static fn(string $c): string => trim($c),
			$erpCodes
		), static fn(string $c): bool => $c !== '')));

		if ($idList === [] && $erpList === []) {
			return ['by_id' => $byId, 'by_erp' => $byErp];
		}

		$conds = [];
		$params = [];
		if ($idList !== []) {
			$ph = [];
			foreach ($idList as $i => $id) {
				$key = ':id' . $i;
				$ph[] = $key;
				$params[$key] = $id;
			}
			$conds[] = 'i.id IN (' . implode(', ', $ph) . ')';
		}
		if ($erpList !== []) {
			$ph = [];
			foreach ($erpList as $i => $erp) {
				$key = ':erp' . $i;
				$ph[] = $key;
				$params[$key] = $erp;
			}
			$conds[] = 'i.erp_code IN (' . implode(', ', $ph) . ')';
		}

		$sql = 'SELECT i.*, u.name AS unit_name, c.name AS category_name
				FROM inv_items i
				LEFT JOIN inv_units u ON u.id = i.inv_unit_id
				LEFT JOIN inv_categories c ON c.id = i.inv_category_id
				WHERE ' . implode(' OR ', $conds);
		$stmt = $this->getConnection()->prepare($sql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmt->execute();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
			$id = (int)($row['id'] ?? 0);
			if ($id > 0) {
				$byId[$id] = $row;
			}
			$erp = trim((string)($row['erp_code'] ?? ''));
			if ($erp !== '') {
				$byErp[mb_strtoupper($erp, 'UTF-8')] = $row;
			}
		}

		return ['by_id' => $byId, 'by_erp' => $byErp];
	}

	/**
	 * Itens ativos com código ERP elegíveis à sincronização de BOM/rota (BEAS).
	 *
	 * @return list<int>
	 */
	public function getIdsEligibleForStructureSync(): array
	{
		$sql = 'SELECT i.id
				FROM inv_items i
				LEFT JOIN inv_categories c ON c.id = i.inv_category_id
				WHERE i.active = 1
				  AND i.erp_code IS NOT NULL
				  AND TRIM(i.erp_code) <> \'\'
				  AND (c.name IS NULL OR c.name <> :project_cat)
				  AND (
				    c.name LIKE :kw_acab
				    OR c.name LIKE :kw_intermed
				    OR i.erp_code LIKE :erp_pa
				    OR i.erp_code LIKE :erp_pi
				    OR EXISTS (SELECT 1 FROM inv_item_bom b WHERE b.inv_item_id = i.id)
				    OR EXISTS (SELECT 1 FROM inv_item_operations o WHERE o.inv_item_id = i.id)
				  )
				ORDER BY i.id';

		$stmt = $this->getConnection()->prepare($sql);
		$stmt->bindValue(':project_cat', InvCostProjectHelper::CATEGORY_NAME);
		$stmt->bindValue(':kw_acab', '%ACAB%');
		$stmt->bindValue(':kw_intermed', '%INTERMED%');
		$stmt->bindValue(':erp_pa', '43%');
		$stmt->bindValue(':erp_pi', '40%');
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

		return array_values(array_filter(array_map(
			static fn(array $row): int => (int)($row['id'] ?? 0),
			$rows
		)));
	}

	public function getIdsByCategoryNameKeywords(array $keywords): array
	{
		$normalized = array_values(array_filter(array_map(static fn($k) => trim((string)$k), $keywords)));
		if ($normalized === []) {
			return [];
		}

		$conds = [];
		$params = [];
		foreach ($normalized as $idx => $keyword) {
			$key = ':kw' . $idx;
			$conds[] = 'c.name LIKE ' . $key;
			$params[$key] = '%' . $keyword . '%';
		}

		$sql = 'SELECT i.id
				FROM inv_items i
				INNER JOIN inv_categories c ON c.id = i.inv_category_id
				WHERE ' . implode(' OR ', $conds);
		$stmt = $this->getConnection()->prepare($sql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value);
		}
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

		return array_map(static fn(array $row): int => (int)($row['id'] ?? 0), $rows);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function getItemRowById(int $id): ?array
	{
		$stmt = $this->getConnection()->prepare('SELECT * FROM inv_items WHERE id = :id LIMIT 1');
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row !== false ? $row : null;
	}

	private function nullableString(mixed $value): ?string
	{
		$s = trim((string)($value ?? ''));

		return $s !== '' ? $s : null;
	}

	private function normalizeComplexityLevel(mixed $value): string
	{
		$level = mb_strtolower(trim((string)($value ?? 'media')), 'UTF-8');

		return in_array($level, ['baixa', 'media', 'alta'], true) ? $level : 'media';
	}
}

