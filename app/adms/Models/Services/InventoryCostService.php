<?php

namespace App\adms\Models\Services;

use PDO;

/**
 * Serviço de cálculo de custo padrão de itens de estoque
 * com base na ficha técnica (BOM) e rota de operações.
 */
class InventoryCostService extends DbConnection
{
    private static function getStaticConnection(): \PDO
    {
        $instance = new class extends DbConnection {
            public function getPublicConnection(): \PDO {
                return $this->getConnection();
            }
        };
        return $instance->getPublicConnection();
    }

    /**
     * Recalcula o custo padrão de um item com base em sua
     * ficha técnica e rota.
     *
     * @param int $itemId
     * @return float Custo calculado (0 se não houver dados suficientes)
     */
    public static function recalculateStandardCost(int $itemId): float
    {
        $breakdown = self::calculateBreakdown($itemId);
        $totalCost = (float)($breakdown['base_total'] ?? 0);

        if ($itemId <= 0) {
            return 0.0;
        }

        $conn = self::getStaticConnection();
        $stmtUpdate = $conn->prepare(
            "UPDATE inv_items 
             SET average_cost = :cost, last_cost = :cost, updated_at = NOW()
             WHERE id = :id"
        );
        $stmtUpdate->bindValue(':cost', $totalCost);
        $stmtUpdate->bindValue(':id', $itemId, PDO::PARAM_INT);
        $stmtUpdate->execute();

        return $totalCost;
    }

    /**
     * Calcula detalhamento de custo (base e simulado) sem gravar no banco.
     *
     * @param int $itemId
     * @param array{
     *   material_adjust_pct?: float,
     *   operations_adjust_pct?: float,
     *   global_adjust_pct?: float
     * } $scenario Percentuais de ajuste (ex.: 5 = +5%, -10 = -10%)
     * @return array<string, mixed>
     */
    public static function calculateBreakdown(int $itemId, array $scenario = []): array
    {
        $empty = [
            'item_id' => $itemId,
            'materials' => [],
            'operations' => [],
            'material_cost' => 0.0,
            'operations_cost' => 0.0,
            'base_total' => 0.0,
            'simulated_material_cost' => 0.0,
            'simulated_operations_cost' => 0.0,
            'simulated_total' => 0.0,
            'scenario' => self::normalizeScenario($scenario),
        ];

        if ($itemId <= 0) {
            return $empty;
        }

        $conn = self::getStaticConnection();
        $scenario = $empty['scenario'];

        $sqlBom = "SELECT 
                        b.quantity_per_batch,
                        b.scrap_percent,
                        i.code AS component_code,
                        i.description AS component_description,
                        i.average_cost AS component_cost
                   FROM inv_item_bom b
                   INNER JOIN inv_items i ON i.id = b.component_item_id
                   WHERE b.inv_item_id = :item_id
                   ORDER BY i.description ASC";
        $stmtBom = $conn->prepare($sqlBom);
        $stmtBom->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmtBom->execute();
        $bomRows = $stmtBom->fetchAll(PDO::FETCH_ASSOC);

        $materials = [];
        $materialCost = 0.0;
        foreach ($bomRows as $row) {
            $line = self::computeMaterialLine($row);
            $materials[] = $line;
            $materialCost += (float)$line['line_cost'];
        }

        $sqlOps = "SELECT 
                        io.time_per_batch_hours,
                        io.time_unit,
                        io.operators_qty,
                        io.labor_cost_per_min,
                        io.machine_cost_per_min,
                        io.energy_cost_per_min,
                        io.notes,
                        op.code AS operation_code,
                        op.name AS operation_name,
                        op.default_cost_per_hour
                   FROM inv_item_operations io
                   INNER JOIN inv_operations op ON op.id = io.inv_operation_id
                   WHERE io.inv_item_id = :item_id
                   ORDER BY io.sequence ASC, op.name ASC";
        $stmtOps = $conn->prepare($sqlOps);
        $stmtOps->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmtOps->execute();
        $opRows = $stmtOps->fetchAll(PDO::FETCH_ASSOC);

        $operations = [];
        $operationsCost = 0.0;
        foreach ($opRows as $row) {
            $line = self::computeOperationLine($row);
            $operations[] = $line;
            $operationsCost += (float)$line['line_cost'];
        }

        $baseTotal = $materialCost + $operationsCost;
        $materialFactor = 1 + ((float)$scenario['material_adjust_pct'] / 100.0);
        $operationsFactor = 1 + ((float)$scenario['operations_adjust_pct'] / 100.0);
        $globalFactor = 1 + ((float)$scenario['global_adjust_pct'] / 100.0);

        $simMaterial = $materialCost * $materialFactor;
        $simOperations = $operationsCost * $operationsFactor;
        $simTotal = ($simMaterial + $simOperations) * $globalFactor;

        return [
            'item_id' => $itemId,
            'materials' => $materials,
            'operations' => $operations,
            'material_cost' => round($materialCost, 6),
            'operations_cost' => round($operationsCost, 6),
            'base_total' => round($baseTotal, 6),
            'simulated_material_cost' => round($simMaterial, 6),
            'simulated_operations_cost' => round($simOperations, 6),
            'simulated_total' => round($simTotal, 6),
            'scenario' => $scenario,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function computeMaterialLine(array $row): array
    {
        $qty = (float)($row['quantity_per_batch'] ?? 0);
        $scrap = (float)($row['scrap_percent'] ?? 0);
        $cost = (float)($row['component_cost'] ?? 0);
        $effectiveQty = $qty * (1 + $scrap / 100.0);
        $lineCost = ($qty > 0 && $cost > 0) ? $effectiveQty * $cost : 0.0;

        return [
            'component_code' => (string)($row['component_code'] ?? ''),
            'component_description' => (string)($row['component_description'] ?? ''),
            'quantity' => $qty,
            'scrap_percent' => $scrap,
            'unit_cost' => $cost,
            'effective_qty' => round($effectiveQty, 6),
            'line_cost' => round($lineCost, 6),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function computeOperationLine(array $row): array
    {
        $rawTime = (float)($row['time_per_batch_hours'] ?? 0);
        $timeUnit = strtoupper((string)($row['time_unit'] ?? 'MIN'));
        $costHour = (float)($row['default_cost_per_hour'] ?? 0);
        $operatorsQty = max(1, (int)($row['operators_qty'] ?? 1));
        $laborCostPerMin = (float)($row['labor_cost_per_min'] ?? 0);
        $machineCostPerMin = (float)($row['machine_cost_per_min'] ?? 0);
        $energyCostPerMin = (float)($row['energy_cost_per_min'] ?? 0);

        if (!in_array($timeUnit, ['MIN', 'H'], true)) {
            $timeUnit = 'MIN';
        }
        $timeMinutes = $timeUnit === 'H' ? $rawTime * 60.0 : $rawTime;

        $lineCost = 0.0;
        $costSource = 'none';
        if ($timeMinutes > 0) {
            $costPerMinuteFromRoute = ($laborCostPerMin * $operatorsQty) + $machineCostPerMin + $energyCostPerMin;
            if ($costPerMinuteFromRoute > 0) {
                $lineCost = $timeMinutes * $costPerMinuteFromRoute;
                $costSource = 'route';
            } elseif ($costHour > 0) {
                $lineCost = ($timeMinutes / 60.0) * $costHour;
                $costSource = 'operation_default';
            }
        }

        return [
            'operation_code' => (string)($row['operation_code'] ?? ''),
            'operation_name' => (string)($row['operation_name'] ?? ''),
            'notes' => (string)($row['notes'] ?? ''),
            'time_minutes' => round($timeMinutes, 6),
            'operators_qty' => $operatorsQty,
            'labor_cost_per_min' => $laborCostPerMin,
            'machine_cost_per_min' => $machineCostPerMin,
            'energy_cost_per_min' => $energyCostPerMin,
            'default_cost_per_hour' => $costHour,
            'cost_source' => $costSource,
            'line_cost' => round($lineCost, 6),
        ];
    }

    /**
     * @param array<string, mixed> $scenario
     * @return array{material_adjust_pct: float, operations_adjust_pct: float, global_adjust_pct: float}
     */
    private static function normalizeScenario(array $scenario): array
    {
        return [
            'material_adjust_pct' => (float)($scenario['material_adjust_pct'] ?? 0),
            'operations_adjust_pct' => (float)($scenario['operations_adjust_pct'] ?? 0),
            'global_adjust_pct' => (float)($scenario['global_adjust_pct'] ?? 0),
        ];
    }
}
