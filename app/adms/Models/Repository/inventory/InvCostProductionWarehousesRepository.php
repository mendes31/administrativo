<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvCostProductionWarehousesRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function getAllActive(): array
    {
        $sql = 'SELECT id, code, name, active, include_in_sync
                FROM inv_cost_production_warehouses
                WHERE active = 1
                ORDER BY code ASC';
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<string>
     */
    public function getCodesForSync(): array
    {
        $sql = 'SELECT code FROM inv_cost_production_warehouses
                WHERE active = 1 AND include_in_sync = 1
                ORDER BY code ASC';
        $stmt = $this->getConnection()->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return array_values(array_filter(array_map('strval', $rows)));
    }
}
