<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvCostProductionBatchesRepository extends DbConnection
{
    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function getAll(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);
        [$whereSql, $params] = $this->buildWhere($filters);

        $sql = 'SELECT b.*, i.code AS item_code
                FROM inv_cost_production_batches b
                LEFT JOIN inv_items i ON i.id = b.inv_item_id
                ' . $whereSql . '
                ORDER BY b.production_date DESC, b.id DESC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countAll(array $filters = []): int
    {
        [$whereSql, $params] = $this->buildWhere($filters);
        $sql = 'SELECT COUNT(*) FROM inv_cost_production_batches b ' . $whereSql;
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    public function findByNaturalKey(
        ?int $baseEntry,
        string $batchNumber,
        string $erpCode,
        ?int $goodsReceiptDocNum,
        string $warehouseCode
    ): ?array {
        $sql = 'SELECT * FROM inv_cost_production_batches
                WHERE batch_number = :batch_number
                  AND erp_code = :erp_code
                  AND warehouse_code = :warehouse_code
                  AND base_entry <=> :base_entry
                  AND goods_receipt_doc_num <=> :goods_receipt_doc_num
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':batch_number', $batchNumber);
        $stmt->bindValue(':erp_code', $erpCode);
        $stmt->bindValue(':warehouse_code', $warehouseCode);
        $stmt->bindValue(':base_entry', $baseEntry, $baseEntry !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':goods_receipt_doc_num', $goodsReceiptDocNum, $goodsReceiptDocNum !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO inv_cost_production_batches (
                    inv_item_id, erp_code, item_description, series_remark,
                    production_date, doc_date, goods_receipt_doc_num, production_order_num,
                    base_type, base_entry, batch_number, mnf_date, exp_date,
                    warehouse_code, warehouse_name, quantity, source,
                    sap_sync_run_id, imported_by, imported_at, updated_at
                ) VALUES (
                    :inv_item_id, :erp_code, :item_description, :series_remark,
                    :production_date, :doc_date, :goods_receipt_doc_num, :production_order_num,
                    :base_type, :base_entry, :batch_number, :mnf_date, :exp_date,
                    :warehouse_code, :warehouse_name, :quantity, :source,
                    :sap_sync_run_id, :imported_by, :imported_at, :updated_at
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindBatchData($stmt, $data, $now);
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE inv_cost_production_batches SET
                    inv_item_id = :inv_item_id,
                    erp_code = :erp_code,
                    item_description = :item_description,
                    series_remark = :series_remark,
                    production_date = :production_date,
                    doc_date = :doc_date,
                    goods_receipt_doc_num = :goods_receipt_doc_num,
                    production_order_num = :production_order_num,
                    base_type = :base_type,
                    base_entry = :base_entry,
                    batch_number = :batch_number,
                    mnf_date = :mnf_date,
                    exp_date = :exp_date,
                    warehouse_code = :warehouse_code,
                    warehouse_name = :warehouse_name,
                    quantity = :quantity,
                    source = :source,
                    sap_sync_run_id = :sap_sync_run_id,
                    imported_by = :imported_by,
                    updated_at = :updated_at
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindBatchData($stmt, $data, $now);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function getMaxProductionDate(): ?string
    {
        $stmt = $this->getConnection()->query('SELECT MAX(production_date) FROM inv_cost_production_batches');
        $value = $stmt->fetchColumn();

        return $value !== false && $value !== null ? (string)$value : null;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhere(array $filters): array
    {
        $wheres = [];
        $params = [];

        if (!empty($filters['erp_code'])) {
            $wheres[] = 'b.erp_code LIKE :erp_code';
            $params[':erp_code'] = '%' . trim((string)$filters['erp_code']) . '%';
        }
        if (!empty($filters['batch_number'])) {
            $wheres[] = 'b.batch_number LIKE :batch_number';
            $params[':batch_number'] = '%' . trim((string)$filters['batch_number']) . '%';
        }
        if (!empty($filters['warehouse_code'])) {
            $wheres[] = 'b.warehouse_code = :warehouse_code';
            $params[':warehouse_code'] = trim((string)$filters['warehouse_code']);
        }
        if (!empty($filters['date_from'])) {
            $wheres[] = 'b.production_date >= :date_from';
            $params[':date_from'] = (string)$filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $wheres[] = 'b.production_date <= :date_to';
            $params[':date_to'] = (string)$filters['date_to'];
        }

        $whereSql = $wheres !== [] ? ('WHERE ' . implode(' AND ', $wheres)) : '';

        return [$whereSql, $params];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function bindBatchData(\PDOStatement $stmt, array $data, string $now): void
    {
        $invItemId = $data['inv_item_id'] ?? null;
        $stmt->bindValue(':inv_item_id', $invItemId !== null ? (int)$invItemId : null, $invItemId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':erp_code', (string)($data['erp_code'] ?? ''));
        $stmt->bindValue(':item_description', $data['item_description'] ?? null);
        $stmt->bindValue(':series_remark', $data['series_remark'] ?? null);
        $stmt->bindValue(':production_date', (string)($data['production_date'] ?? ''));
        $stmt->bindValue(':doc_date', $data['doc_date'] ?? null);
        $stmt->bindValue(':goods_receipt_doc_num', $data['goods_receipt_doc_num'] ?? null, isset($data['goods_receipt_doc_num']) && $data['goods_receipt_doc_num'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':production_order_num', $data['production_order_num'] ?? null, isset($data['production_order_num']) && $data['production_order_num'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':base_type', $data['base_type'] ?? null, isset($data['base_type']) && $data['base_type'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':base_entry', $data['base_entry'] ?? null, isset($data['base_entry']) && $data['base_entry'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':batch_number', (string)($data['batch_number'] ?? ''));
        $stmt->bindValue(':mnf_date', $data['mnf_date'] ?? null);
        $stmt->bindValue(':exp_date', $data['exp_date'] ?? null);
        $stmt->bindValue(':warehouse_code', (string)($data['warehouse_code'] ?? ''));
        $stmt->bindValue(':warehouse_name', $data['warehouse_name'] ?? null);
        $stmt->bindValue(':quantity', (float)($data['quantity'] ?? 0));
        $stmt->bindValue(':source', (string)($data['source'] ?? 'SAP'));
        $syncRunId = $data['sap_sync_run_id'] ?? null;
        $stmt->bindValue(':sap_sync_run_id', $syncRunId !== null ? (int)$syncRunId : null, $syncRunId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $importedBy = $data['imported_by'] ?? null;
        $stmt->bindValue(':imported_by', $importedBy !== null ? (int)$importedBy : null, $importedBy !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':imported_at', (string)($data['imported_at'] ?? $now));
        $stmt->bindValue(':updated_at', $now);
    }
}
