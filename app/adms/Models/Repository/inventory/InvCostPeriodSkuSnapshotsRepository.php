<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvCostPeriodSkuSnapshotsRepository extends DbConnection
{
    public function deleteByPeriod(int $periodId): void
    {
        if ($periodId <= 0) {
            return;
        }

        $stmt = $this->getConnection()->prepare(
            'DELETE FROM inv_cost_period_sku_snapshots WHERE inv_cost_period_id = :period_id'
        );
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function countByPeriod(int $periodId): int
    {
        if ($periodId <= 0) {
            return 0;
        }

        $stmt = $this->getConnection()->prepare(
            'SELECT COUNT(*) FROM inv_cost_period_sku_snapshots WHERE inv_cost_period_id = :period_id'
        );
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function insertBatch(int $periodId, array $rows): int
    {
        if ($periodId <= 0 || $rows === []) {
            return 0;
        }

        $sql = 'INSERT INTO inv_cost_period_sku_snapshots (
            inv_cost_period_id, inv_item_id, erp_code, item_description, category_name,
            linked, is_scenario, has_scenario, is_produto_acabado,
            batches_count, total_qty, qty_planned, efficiency_ratio, efficiency_pct, standard_batch_size,
            batch_size_adopted, qty_avg_per_round, batches_produced,
            energy_class, complexity_level, complexity_factor, production_line,
            mp_lines, mae_lines, analysis_lines_per_batch, analysis_count_total,
            driver_4, driver_6, share_criterion_4, share_criterion_6,
            energy_class_from_item, suggested_energy_class,
            cvar_sim_unit, cvar_mp_unit, cvar_mae_unit, cvar_energy_unit, cvar_batch,
            cfix_total, cfix_unit, cfix_batch, full_cost_unit, full_cost_batch,
            sale_price_net, markup_pct,
            computed_at
        ) VALUES (
            :inv_cost_period_id, :inv_item_id, :erp_code, :item_description, :category_name,
            :linked, :is_scenario, :has_scenario, :is_produto_acabado,
            :batches_count, :total_qty, :qty_planned, :efficiency_ratio, :efficiency_pct, :standard_batch_size,
            :batch_size_adopted, :qty_avg_per_round, :batches_produced,
            :energy_class, :complexity_level, :complexity_factor, :production_line,
            :mp_lines, :mae_lines, :analysis_lines_per_batch, :analysis_count_total,
            :driver_4, :driver_6, :share_criterion_4, :share_criterion_6,
            :energy_class_from_item, :suggested_energy_class,
            :cvar_sim_unit, :cvar_mp_unit, :cvar_mae_unit, :cvar_energy_unit, :cvar_batch,
            :cfix_total, :cfix_unit, :cfix_batch, :full_cost_unit, :full_cost_batch,
            :sale_price_net, :markup_pct,
            :computed_at
        )';

        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $inserted = 0;

        foreach ($rows as $row) {
            $this->bindSnapshotRow($stmt, $periodId, $row);
            $stmt->execute();
            $inserted++;
        }

        return $inserted;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByPeriod(int $periodId): array
    {
        if ($periodId <= 0) {
            return [];
        }

        $sql = 'SELECT * FROM inv_cost_period_sku_snapshots
            WHERE inv_cost_period_id = :period_id
            ORDER BY item_description ASC, erp_code ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function bindSnapshotRow(\PDOStatement $stmt, int $periodId, array $row): void
    {
        $bindNullable = static function (\PDOStatement $s, string $param, mixed $value): void {
            if ($value === null || $value === '') {
                $s->bindValue($param, null, PDO::PARAM_NULL);

                return;
            }
            $s->bindValue($param, $value);
        };

        $stmt->bindValue(':inv_cost_period_id', $periodId, PDO::PARAM_INT);
        $itemId = (int)($row['inv_item_id'] ?? 0);
        $stmt->bindValue(':inv_item_id', $itemId > 0 ? $itemId : null, $itemId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':erp_code', (string)($row['erp_code'] ?? ''));
        $bindNullable($stmt, ':item_description', $row['item_description'] ?? null);
        $bindNullable($stmt, ':category_name', $row['category_name'] ?? null);
        $stmt->bindValue(':linked', !empty($row['linked']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':is_scenario', !empty($row['is_scenario']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':has_scenario', !empty($row['has_scenario']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':is_produto_acabado', !empty($row['is_produto_acabado']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':batches_count', (int)($row['batches_count'] ?? 0), PDO::PARAM_INT);
        $bindNullable($stmt, ':total_qty', $row['total_qty'] ?? null);
        $bindNullable($stmt, ':qty_planned', $row['qty_planned'] ?? null);
        $bindNullable($stmt, ':efficiency_ratio', $row['efficiency_ratio'] ?? null);
        $bindNullable($stmt, ':efficiency_pct', $row['efficiency_pct'] ?? null);
        $bindNullable($stmt, ':standard_batch_size', $row['standard_batch_size'] ?? null);
        $bindNullable($stmt, ':batch_size_adopted', $row['batch_size_adopted'] ?? null);
        $bindNullable($stmt, ':qty_avg_per_round', $row['qty_avg_per_round'] ?? null);
        $bindNullable($stmt, ':batches_produced', $row['batches_produced'] ?? null);
        $bindNullable($stmt, ':energy_class', $row['energy_class'] ?? null);
        $bindNullable($stmt, ':complexity_level', $row['complexity_level'] ?? null);
        $bindNullable($stmt, ':complexity_factor', $row['complexity_factor'] ?? null);
        $bindNullable($stmt, ':production_line', $row['production_line'] ?? null);
        $stmt->bindValue(':mp_lines', (int)($row['mp_lines'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':mae_lines', (int)($row['mae_lines'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':analysis_lines_per_batch', (int)($row['analysis_lines_per_batch'] ?? 0), PDO::PARAM_INT);
        $bindNullable($stmt, ':analysis_count_total', $row['analysis_count_total'] ?? null);
        $bindNullable($stmt, ':driver_4', $row['driver_4'] ?? null);
        $bindNullable($stmt, ':driver_6', $row['driver_6'] ?? null);
        $bindNullable($stmt, ':share_criterion_4', $row['share_criterion_4'] ?? null);
        $bindNullable($stmt, ':share_criterion_6', $row['share_criterion_6'] ?? null);
        $stmt->bindValue(':energy_class_from_item', !empty($row['energy_class_from_item']) ? 1 : 0, PDO::PARAM_INT);
        $bindNullable($stmt, ':suggested_energy_class', $row['suggested_energy_class'] ?? null);
        $bindNullable($stmt, ':cvar_sim_unit', $row['cvar_sim_unit'] ?? null);
        $bindNullable($stmt, ':cvar_mp_unit', $row['cvar_mp_unit'] ?? null);
        $bindNullable($stmt, ':cvar_mae_unit', $row['cvar_mae_unit'] ?? null);
        $bindNullable($stmt, ':cvar_energy_unit', $row['cvar_energy_unit'] ?? null);
        $bindNullable($stmt, ':cvar_batch', $row['cvar_batch'] ?? null);
        $bindNullable($stmt, ':cfix_total', $row['cfix_total'] ?? null);
        $bindNullable($stmt, ':cfix_unit', $row['cfix_unit'] ?? null);
        $bindNullable($stmt, ':cfix_batch', $row['cfix_batch'] ?? null);
        $bindNullable($stmt, ':full_cost_unit', $row['full_cost_unit'] ?? null);
        $bindNullable($stmt, ':full_cost_batch', $row['full_cost_batch'] ?? null);
        $bindNullable($stmt, ':sale_price_net', $row['sale_price_net'] ?? null);
        $bindNullable($stmt, ':markup_pct', $row['markup_pct'] ?? null);
        $bindNullable($stmt, ':computed_at', $row['computed_at'] ?? null);
    }
}
