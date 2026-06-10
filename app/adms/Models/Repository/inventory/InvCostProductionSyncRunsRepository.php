<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvCostProductionSyncRunsRepository extends DbConnection
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO inv_cost_production_sync_runs (
                    source, sync_mode, warehouse_codes_synced, filter_from_date,
                    rows_inserted, rows_updated, rows_skipped, status, error_log,
                    started_at, finished_at
                ) VALUES (
                    :source, :sync_mode, :warehouse_codes_synced, :filter_from_date,
                    :rows_inserted, :rows_updated, :rows_skipped, :status, :error_log,
                    :started_at, :finished_at
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':source', (string)($data['source'] ?? 'SAP'));
        $stmt->bindValue(':sync_mode', (string)($data['sync_mode'] ?? 'full'));
        $stmt->bindValue(':warehouse_codes_synced', $data['warehouse_codes_synced'] ?? null);
        $stmt->bindValue(':filter_from_date', $data['filter_from_date'] ?? null);
        $stmt->bindValue(':rows_inserted', (int)($data['rows_inserted'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_updated', (int)($data['rows_updated'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_skipped', (int)($data['rows_skipped'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':status', (string)($data['status'] ?? 'running'));
        $stmt->bindValue(':error_log', $data['error_log'] ?? null);
        $stmt->bindValue(':started_at', (string)($data['started_at'] ?? date('Y-m-d H:i:s')));
        $stmt->bindValue(':finished_at', $data['finished_at'] ?? null);
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE inv_cost_production_sync_runs SET
                    rows_inserted = :rows_inserted,
                    rows_updated = :rows_updated,
                    rows_skipped = :rows_skipped,
                    status = :status,
                    error_log = :error_log,
                    finished_at = :finished_at
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':rows_inserted', (int)($data['rows_inserted'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_updated', (int)($data['rows_updated'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_skipped', (int)($data['rows_skipped'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':status', (string)($data['status'] ?? 'completed'));
        $stmt->bindValue(':error_log', $data['error_log'] ?? null);
        $stmt->bindValue(':finished_at', (string)($data['finished_at'] ?? date('Y-m-d H:i:s')));
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function getLastSuccessful(): array|false
    {
        $sql = 'SELECT * FROM inv_cost_production_sync_runs
                WHERE status = :status
                ORDER BY finished_at DESC, id DESC
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':status', 'completed');
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
