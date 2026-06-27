<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvInventorySapSyncRunsRepository extends DbConnection
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        if (!$this->tableExists()) {
            return 0;
        }
        $sql = 'INSERT INTO inv_inventory_sap_sync_runs (
                    sync_type, sync_mode, filter_from_date,
                    rows_created, rows_updated, rows_unchanged, rows_failed,
                    status, error_log, started_at, finished_at
                ) VALUES (
                    :sync_type, :sync_mode, :filter_from_date,
                    :rows_created, :rows_updated, :rows_unchanged, :rows_failed,
                    :status, :error_log, :started_at, :finished_at
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':sync_type', (string)($data['sync_type'] ?? 'items'));
        $stmt->bindValue(':sync_mode', (string)($data['sync_mode'] ?? 'incremental'));
        $stmt->bindValue(':filter_from_date', $data['filter_from_date'] ?? null);
        $stmt->bindValue(':rows_created', (int)($data['rows_created'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_updated', (int)($data['rows_updated'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_unchanged', (int)($data['rows_unchanged'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_failed', (int)($data['rows_failed'] ?? 0), PDO::PARAM_INT);
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
        if ($id <= 0 || !$this->tableExists()) {
            return false;
        }
        $sql = 'UPDATE inv_inventory_sap_sync_runs SET
                    rows_created = :rows_created,
                    rows_updated = :rows_updated,
                    rows_unchanged = :rows_unchanged,
                    rows_failed = :rows_failed,
                    status = :status,
                    error_log = :error_log,
                    finished_at = :finished_at
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':rows_created', (int)($data['rows_created'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_updated', (int)($data['rows_updated'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_unchanged', (int)($data['rows_unchanged'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':rows_failed', (int)($data['rows_failed'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':status', (string)($data['status'] ?? 'completed'));
        $stmt->bindValue(':error_log', $data['error_log'] ?? null);
        $stmt->bindValue(':finished_at', (string)($data['finished_at'] ?? date('Y-m-d H:i:s')));
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function getLastSuccessfulFinishedAt(string $syncType): ?string
    {
        if (!$this->tableExists()) {
            return null;
        }

        $sql = 'SELECT finished_at FROM inv_inventory_sap_sync_runs
                WHERE sync_type = :sync_type AND status = :status
                ORDER BY finished_at DESC, id DESC
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':sync_type', $syncType);
        $stmt->bindValue(':status', 'completed');
        $stmt->execute();
        $value = $stmt->fetchColumn();

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function tableExists(): bool
    {
        try {
            $stmt = $this->getConnection()->query(
                "SELECT 1 FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = 'inv_inventory_sap_sync_runs'
                 LIMIT 1"
            );

            return (bool)$stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }
}
