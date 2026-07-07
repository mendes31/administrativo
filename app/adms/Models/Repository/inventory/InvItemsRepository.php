<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\InvCostComplexityHelper;
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
		[$wheres, $params] = $this->buildListFilters($filters, 'i.');
		$whereSql = $wheres !== [] ? ('WHERE ' . implode(' AND ', $wheres)) : '';

        $sql = 'SELECT i.id, i.code, i.erp_code, i.description, i.admin_type, i.average_cost, i.min_stock, i.max_stock, i.active,
				i.production_line, u.name AS unit_name, c.name AS category_name, pf.name AS pharma_form_name,
				COALESCE(SUM(b.qty), 0) AS total_qty
			FROM inv_items i
			LEFT JOIN inv_units u ON u.id = i.inv_unit_id
			LEFT JOIN inv_categories c ON c.id = i.inv_category_id
			LEFT JOIN inv_pharma_forms pf ON pf.id = i.inv_pharma_form_id
			LEFT JOIN inv_balances b ON b.inv_item_id = i.id
			' . $whereSql . '
			GROUP BY i.id, i.code, i.erp_code, i.description, i.admin_type, i.average_cost, i.min_stock, i.max_stock, i.active,
				i.production_line, u.name, c.name, pf.name
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
		[$wheres, $params] = $this->buildListFilters($filters);
		$whereSql = $wheres !== [] ? ('WHERE ' . implode(' AND ', $wheres)) : '';
		$sql = 'SELECT COUNT(*) AS total FROM inv_items i ' . $whereSql;
		$stmt = $this->getConnection()->prepare($sql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return (int)($row['total'] ?? 0);
	}

	/**
	 * @return array{0: list<string>, 1: array<string, mixed>}
	 */
	private function buildListFilters(array $filters, string $prefix = ''): array
	{
		$wheres = [];
		$params = [];
		$p = $prefix;

		if (!empty($filters['code'])) {
			$wheres[] = $p . 'code LIKE :code';
			$params[':code'] = '%' . $filters['code'] . '%';
		}
		if (!empty($filters['description'])) {
			$wheres[] = $p . 'description LIKE :description';
			$params[':description'] = '%' . $filters['description'] . '%';
		}
		if (isset($filters['active']) && $filters['active'] !== '') {
			$wheres[] = $p . 'active = :active';
			$params[':active'] = (int)$filters['active'];
		}
		if (!empty($filters['categoria_id'])) {
			$wheres[] = $p . 'inv_category_id = :categoria_id';
			$params[':categoria_id'] = (int)$filters['categoria_id'];
		}
		if (!empty($filters['production_line'])) {
			if ((string)$filters['production_line'] === '__empty__') {
				$wheres[] = '(' . $p . 'production_line IS NULL OR TRIM(' . $p . 'production_line) = \'\')';
			} else {
				$wheres[] = $p . 'production_line = :production_line';
				$params[':production_line'] = (string)$filters['production_line'];
			}
		}
		if (!empty($filters['inv_pharma_form_id'])) {
			$wheres[] = $p . 'inv_pharma_form_id = :inv_pharma_form_id';
			$params[':inv_pharma_form_id'] = (int)$filters['inv_pharma_form_id'];
		}

		return [$wheres, $params];
	}

	/**
	 * Item anterior/próximo na mesma ordem da listagem (code ASC, id ASC) com filtros aplicados.
	 *
	 * @param array<string, mixed> $filters
	 * @return array{
	 *   prev: ?array{id: int, code: string, description: string},
	 *   next: ?array{id: int, code: string, description: string}
	 * }
	 */
	public function findListNeighbors(int $currentId, array $filters = []): array
	{
		$result = ['prev' => null, 'next' => null];
		if ($currentId <= 0) {
			return $result;
		}

		$current = $this->getOne($currentId);
		if (!is_array($current)) {
			return $result;
		}

		$curCode = trim((string)($current['code'] ?? ''));
		[$wheres, $params] = $this->buildListFilters($filters, 'i.');
		$baseWhere = $wheres !== [] ? implode(' AND ', $wheres) : '1=1';

		$nextSql = 'SELECT i.id, i.code, i.description
			FROM inv_items i
			WHERE ' . $baseWhere . ' AND (i.code > :cur_code OR (i.code = :cur_code AND i.id > :cur_id))
			ORDER BY i.code ASC, i.id ASC
			LIMIT 1';
		$stmt = $this->getConnection()->prepare($nextSql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmt->bindValue(':cur_code', $curCode, PDO::PARAM_STR);
		$stmt->bindValue(':cur_id', $currentId, PDO::PARAM_INT);
		$stmt->execute();
		$next = $stmt->fetch(PDO::FETCH_ASSOC);
		if (is_array($next)) {
			$result['next'] = [
				'id' => (int)($next['id'] ?? 0),
				'code' => trim((string)($next['code'] ?? '')),
				'description' => trim((string)($next['description'] ?? '')),
			];
		}

		$prevSql = 'SELECT i.id, i.code, i.description
			FROM inv_items i
			WHERE ' . $baseWhere . ' AND (i.code < :cur_code OR (i.code = :cur_code AND i.id < :cur_id))
			ORDER BY i.code DESC, i.id DESC
			LIMIT 1';
		$stmt = $this->getConnection()->prepare($prevSql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmt->bindValue(':cur_code', $curCode, PDO::PARAM_STR);
		$stmt->bindValue(':cur_id', $currentId, PDO::PARAM_INT);
		$stmt->execute();
		$prev = $stmt->fetch(PDO::FETCH_ASSOC);
		if (is_array($prev)) {
			$result['prev'] = [
				'id' => (int)($prev['id'] ?? 0),
				'code' => trim((string)($prev['code'] ?? '')),
				'description' => trim((string)($prev['description'] ?? '')),
			];
		}

		return $result;
	}

	public function getOne(int $id): array|bool
	{
        $sql = 'SELECT i.*, u.name AS unit_name, c.name AS category_name, pf.name AS pharma_form_name
			FROM inv_items i
			LEFT JOIN inv_units u ON u.id = i.inv_unit_id
			LEFT JOIN inv_categories c ON c.id = i.inv_category_id
			LEFT JOIN inv_pharma_forms pf ON pf.id = i.inv_pharma_form_id
			WHERE i.id = :id LIMIT 1';
		$stmt = $this->getConnection()->prepare($sql);
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function create(array $data): int|bool
	{
		try {
            $sql = 'INSERT INTO inv_items (code, erp_code, description, inv_unit_id, inv_category_id, admin_type, average_cost, last_cost, min_stock, max_stock, standard_batch_size, energy_class, complexity_level, production_line, inv_pharma_form_id, sap_update_date, active, created_at)
                VALUES (:code, :erp_code, :description, :inv_unit_id, :inv_category_id, :admin_type, :average_cost, :last_cost, :min_stock, :max_stock, :standard_batch_size, :energy_class, :complexity_level, :production_line, :inv_pharma_form_id, :sap_update_date, :active, :created_at)';
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
			$stmt->bindValue(':complexity_level', $this->normalizeComplexityLevel($data['complexity_level'] ?? InvCostComplexityHelper::LEVEL_NA));
			$stmt->bindValue(':production_line', $this->nullableString($data['production_line'] ?? null), $this->nullableString($data['production_line'] ?? null) !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
			$pharmaFormIdCreate = !empty($data['inv_pharma_form_id']) ? (int)$data['inv_pharma_form_id'] : null;
			$stmt->bindValue(':inv_pharma_form_id', $pharmaFormIdCreate, $pharmaFormIdCreate !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
			$sapUpdateDate = $this->nullableString($data['sap_update_date'] ?? null);
			$stmt->bindValue(':sap_update_date', $sapUpdateDate, $sapUpdateDate !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
			$stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
			$stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
			$ok = $stmt->execute();
			$rowCount = $stmt->rowCount();
			$newId = (int) $this->getConnection()->lastInsertId();
			$this->writeSapInsertRepositoryAudit((string)($data['erp_code'] ?? $data['code'] ?? ''), $ok, $rowCount, $newId, null, $data);
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
			$this->writeSapInsertRepositoryAudit((string)($data['erp_code'] ?? $data['code'] ?? ''), false, 0, 0, $e->getMessage(), $data);
			GenerateLog::generateLog('error', 'Falha ao criar item de estoque', ['error' => $e->getMessage(), 'code' => $data['code'] ?? '']);
			return false;
		}
	}

	/**
	 * Log técnico de INSERT no repository. Ajuda a identificar INSERT silencioso, lastInsertId=0,
	 * rowCount=0 ou exceções capturadas.
	 *
	 * @param array<string, mixed> $payload
	 */
	private function writeSapInsertRepositoryAudit(string $erpCode, bool $ok, int $rowCount, int $newId, ?string $error, array $payload): void
	{
		$logDir = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'logs';
		if (!is_dir($logDir)) {
			@mkdir($logDir, 0775, true);
		}

		$lines = [];
		$lines[] = '[' . date('Y-m-d H:i:s') . '] REPOSITORY INSERT inv_items | ' . $erpCode;
		$lines[] = '  execute_ok: ' . ($ok ? 'SIM' : 'NÃO');
		$lines[] = '  row_count: ' . $rowCount;
		$lines[] = '  last_insert_id: ' . $newId;
		if ($error !== null && $error !== '') {
			$lines[] = '  error: ' . $error;
		}
		$lines[] = '  payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$lines[] = '';

		@file_put_contents(
			$logDir . DIRECTORY_SEPARATOR . 'sap_sync_repository_insert_audit.log',
			implode(PHP_EOL, $lines) . PHP_EOL,
			FILE_APPEND | LOCK_EX
		);
	}

	public function update(int $id, array $data): bool
	{
		try {
			$oldRow = $this->getItemRowById($id);
            $sql = 'UPDATE inv_items SET code = :code, erp_code = :erp_code, description = :description, inv_unit_id = :inv_unit_id, inv_category_id = :inv_category_id,
				admin_type = :admin_type, average_cost = :average_cost, last_cost = :last_cost, min_stock = :min_stock, max_stock = :max_stock,
				standard_batch_size = :standard_batch_size, energy_class = :energy_class, complexity_level = :complexity_level,
                production_line = :production_line, inv_pharma_form_id = :inv_pharma_form_id, sap_update_date = :sap_update_date, active = :active, updated_at = :updated_at WHERE id = :id';
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
			$stmt->bindValue(':complexity_level', $this->normalizeComplexityLevel($data['complexity_level'] ?? ($oldRow['complexity_level'] ?? InvCostComplexityHelper::LEVEL_NA)));
			$stmt->bindValue(':production_line', $this->nullableString($data['production_line'] ?? null), $this->nullableString($data['production_line'] ?? null) !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
			$pharmaFormIdUpdate = !empty($data['inv_pharma_form_id']) ? (int)$data['inv_pharma_form_id'] : null;
			$stmt->bindValue(':inv_pharma_form_id', $pharmaFormIdUpdate, $pharmaFormIdUpdate !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
			$sapUpdateDateUpdate = $this->nullableString($data['sap_update_date'] ?? null);
			$stmt->bindValue(':sap_update_date', $sapUpdateDateUpdate, $sapUpdateDateUpdate !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
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

	/**
	 * Snapshot local para diff na sincronização SAP (uma query).
	 *
	 * @return array<string, array<string, mixed>> erp_code => dados comparáveis
	 */
	public function getSapSyncSnapshot(): array
	{
		$sql = 'SELECT i.id, i.code, i.erp_code, i.description, i.active, i.inv_unit_id, i.inv_category_id,
				i.average_cost, i.last_cost, i.production_line, i.inv_pharma_form_id, i.sap_update_date,
				i.energy_class, i.complexity_level, i.admin_type, i.min_stock, i.max_stock, i.standard_batch_size,
				i.sap_item_hash, i.sap_beas_version, i.sap_bom_hash, i.sap_route_hash,
				i.sap_structure_pending, i.sap_route_pending, i.sap_last_synced_at,
				u.code AS unit_code, c.name AS category_name
			FROM inv_items i
			LEFT JOIN inv_units u ON u.id = i.inv_unit_id
			LEFT JOIN inv_categories c ON c.id = i.inv_category_id
			WHERE i.erp_code IS NOT NULL AND TRIM(i.erp_code) <> \'\'';
		$stmt = $this->getConnection()->query($sql);
		$map = [];
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
			$erp = trim((string)($row['erp_code'] ?? ''));
			if ($erp === '') {
				continue;
			}
			$map[mb_strtoupper($erp, 'UTF-8')] = $row;
		}

		return $map;
	}

	/** Maior código ERP já sincronizado (retomada quando checkpoint ausente). */
	public function getMaxSyncedErpCode(): ?string
	{
		$sql = 'SELECT erp_code FROM inv_items
			WHERE erp_code IS NOT NULL AND TRIM(erp_code) <> \'\' AND sap_update_date IS NOT NULL
			ORDER BY CAST(erp_code AS UNSIGNED) DESC, erp_code DESC
			LIMIT 1';
		$stmt = $this->getConnection()->query($sql);
		$value = $stmt->fetchColumn();

		return is_string($value) && trim($value) !== '' ? trim($value) : null;
	}

	public function countWithSapSyncDate(): int
	{
		$stmt = $this->getConnection()->query(
			'SELECT COUNT(*) FROM inv_items WHERE erp_code IS NOT NULL AND TRIM(erp_code) <> \'\' AND sap_update_date IS NOT NULL'
		);

		return (int)$stmt->fetchColumn();
	}

	/**
	 * PA/PI com forma farmacêutica ou linha de produção ausente.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function getPaPiItemsMissingUdf(): array
	{
		$sql = 'SELECT i.*, c.name AS category_name
			FROM inv_items i
			INNER JOIN inv_categories c ON c.id = i.inv_category_id
			WHERE i.erp_code IS NOT NULL AND TRIM(i.erp_code) <> \'\'
			AND (
				UPPER(c.name) LIKE \'%ACABADO%\'
				OR UPPER(c.name) LIKE \'%INTERMED%\'
				OR (UPPER(c.name) LIKE \'%PROD%\' AND UPPER(c.name) NOT LIKE \'%MP%\')
			)
			AND (
				i.production_line IS NULL OR TRIM(i.production_line) = \'\'
				OR i.inv_pharma_form_id IS NULL
			)
			ORDER BY i.erp_code ASC';
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
	 * Itens com código ERP para purge pós-sync (cadastros manuais sem ERP ficam de fora).
	 *
	 * @return list<array<string, mixed>>
	 */
	public function getSapLinkedItemsForPurge(): array
	{
		$sql = 'SELECT i.id, i.erp_code, c.name AS category_name
				FROM inv_items i
				LEFT JOIN inv_categories c ON c.id = i.inv_category_id
				WHERE i.erp_code IS NOT NULL AND TRIM(i.erp_code) <> \'\'';
		$stmt = $this->getConnection()->query($sql);

		return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
	public function getIdsEligibleForStructureSync(?string $filterGroupPrefix = null): array
	{
		$filterGroupPrefix = trim((string)($filterGroupPrefix ?? ''));
		$sql = 'SELECT i.id
				FROM inv_items i
				LEFT JOIN inv_categories c ON c.id = i.inv_category_id
				WHERE i.erp_code IS NOT NULL
				  AND TRIM(i.erp_code) <> \'\'
				  AND (c.name IS NULL OR c.name <> :project_cat)
				  AND (
				    c.name LIKE :kw_acab
				    OR c.name LIKE :kw_intermed
				    OR i.erp_code LIKE :erp_pa
				    OR i.erp_code LIKE :erp_pi
				    OR EXISTS (SELECT 1 FROM inv_item_bom b WHERE b.inv_item_id = i.id)
				    OR EXISTS (SELECT 1 FROM inv_item_operations o WHERE o.inv_item_id = i.id)
				  )'
				. ($filterGroupPrefix !== '' ? ' AND c.name LIKE :filter_group_prefix ' : '') .
				' ORDER BY i.id';

		$stmt = $this->getConnection()->prepare($sql);
		$stmt->bindValue(':project_cat', InvCostProjectHelper::CATEGORY_NAME);
		$stmt->bindValue(':kw_acab', '%ACAB%');
		$stmt->bindValue(':kw_intermed', '%INTERMED%');
		$stmt->bindValue(':erp_pa', '43%');
		$stmt->bindValue(':erp_pi', '40%');
		if ($filterGroupPrefix !== '') {
			$stmt->bindValue(':filter_group_prefix', $filterGroupPrefix . '%');
		}
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

		return array_values(array_filter(array_map(
			static fn(array $row): int => (int)($row['id'] ?? 0),
			$rows
		)));
	}

	/**
	 * Itens com BOM e/ou rota marcados como pendentes (fila em inv_items, sem tabela extra).
	 *
	 * @return list<int>
	 */
	public function getIdsPendingStructureSync(?string $filterGroupPrefix = null): array
	{
		$filterGroupPrefix = trim((string)($filterGroupPrefix ?? ''));
		$sql = 'SELECT i.id
				FROM inv_items i
				LEFT JOIN inv_categories c ON c.id = i.inv_category_id
				WHERE i.erp_code IS NOT NULL
				  AND TRIM(i.erp_code) <> \'\'
				  AND (c.name IS NULL OR c.name <> :project_cat)
				  AND (i.sap_structure_pending = 1 OR i.sap_route_pending = 1)'
				. ($filterGroupPrefix !== '' ? ' AND c.name LIKE :filter_group_prefix ' : '') .
				' ORDER BY i.erp_code ASC';

		$stmt = $this->getConnection()->prepare($sql);
		$stmt->bindValue(':project_cat', InvCostProjectHelper::CATEGORY_NAME);
		if ($filterGroupPrefix !== '') {
			$stmt->bindValue(':filter_group_prefix', $filterGroupPrefix . '%');
		}
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

		return array_values(array_filter(array_map(
			static fn(array $row): int => (int)($row['id'] ?? 0),
			$rows
		)));
	}

	public function countPendingStructureSync(): int
	{
		$sql = 'SELECT COUNT(*)
				FROM inv_items i
				LEFT JOIN inv_categories c ON c.id = i.inv_category_id
				WHERE i.erp_code IS NOT NULL
				  AND TRIM(i.erp_code) <> \'\'
				  AND (c.name IS NULL OR c.name <> :project_cat)
				  AND (i.sap_structure_pending = 1 OR i.sap_route_pending = 1)';
		$stmt = $this->getConnection()->prepare($sql);
		$stmt->bindValue(':project_cat', InvCostProjectHelper::CATEGORY_NAME);
		$stmt->execute();

		return (int)$stmt->fetchColumn();
	}

	/**
	 * @param array<string, mixed> $meta
	 */
	public function updateSapSyncMetadata(int $id, array $meta): bool
	{
		if ($id <= 0 || $meta === []) {
			return false;
		}

		$fields = [];
		$params = [':id' => $id];

		foreach ([
			'sap_item_hash' => 'sap_item_hash',
			'sap_beas_version' => 'sap_beas_version',
			'sap_update_date' => 'sap_update_date',
			'sap_bom_hash' => 'sap_bom_hash',
			'sap_route_hash' => 'sap_route_hash',
			'sap_last_synced_at' => 'sap_last_synced_at',
		] as $key => $column) {
			if (!array_key_exists($key, $meta)) {
				continue;
			}
			$value = $this->nullableString($meta[$key]);
			$fields[] = $column . ' = :' . $column;
			$params[':' . $column] = $value;
		}

		if (array_key_exists('sap_structure_pending', $meta)) {
			$fields[] = 'sap_structure_pending = :sap_structure_pending';
			$params[':sap_structure_pending'] = !empty($meta['sap_structure_pending']) ? 1 : 0;
		}

		if (array_key_exists('sap_route_pending', $meta)) {
			$fields[] = 'sap_route_pending = :sap_route_pending';
			$params[':sap_route_pending'] = !empty($meta['sap_route_pending']) ? 1 : 0;
		}

		if ($fields === []) {
			return false;
		}

		$sql = 'UPDATE inv_items SET ' . implode(', ', $fields) . ' WHERE id = :id';
		$stmt = $this->getConnection()->prepare($sql);
		foreach ($params as $key => $value) {
			if ($key === ':id') {
				$stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
				continue;
			}
			if ($value === null) {
				$stmt->bindValue($key, null, PDO::PARAM_NULL);
			} elseif (in_array($key, [':sap_structure_pending', ':sap_route_pending'], true)) {
				$stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
			} else {
				$stmt->bindValue($key, (string)$value);
			}
		}

		return $stmt->execute();
	}


	/**
	 * Atualiza somente o UpdateDate do SAP para itens antigos já conferidos.
	 * Não altera updated_at, não recalcula custos, não mexe em BOM/rota e não passa pelo update completo do item.
	 */
	public function updateSapUpdateDateOnly(int $id, ?string $sapUpdateDate): bool
	{
		if ($id <= 0) {
			return false;
		}

		$value = $this->nullableString($sapUpdateDate);
		if ($value === null) {
			return false;
		}

		$sql = 'UPDATE inv_items
				SET sap_update_date = :sap_update_date
				WHERE id = :id
				  AND (sap_update_date IS NULL OR sap_update_date <> :sap_update_date_check)';
		$stmt = $this->getConnection()->prepare($sql);
		$stmt->bindValue(':sap_update_date', $value, PDO::PARAM_STR);
		$stmt->bindValue(':sap_update_date_check', $value, PDO::PARAM_STR);
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);

		return $stmt->execute();
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
		return InvCostComplexityHelper::resolveForCosting($value);
	}
}

