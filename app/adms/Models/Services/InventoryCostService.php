<?php

namespace App\adms\Models\Services;

use PDO;

/**
 * Serviço de cálculo de custo padrão de itens de estoque
 * com base na ficha técnica (BOM) e rota de operações.
 *
 * V1: considera quantidade da BOM e tempo de rota como custo
 * por unidade, usando:
 * - inv_item_bom.quantity_per_batch (por unidade)
 * - inv_items.average_cost (dos componentes)
 * - inv_item_operations.time_per_batch_hours
 * - inv_operations.default_cost_per_hour
 *
 * Resultado: grava o custo calculado em inv_items.average_cost
 * e também em last_cost, para visualização nas telas.
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
        if ($itemId <= 0) {
            return 0.0;
        }

        $conn = self::getStaticConnection();

        // Custo de materiais (BOM)
        $sqlBom = "SELECT 
                        b.quantity_per_batch,
                        b.scrap_percent,
                        i.average_cost AS component_cost
                   FROM inv_item_bom b
                   INNER JOIN inv_items i ON i.id = b.component_item_id
                   WHERE b.inv_item_id = :item_id";
        $stmtBom = $conn->prepare($sqlBom);
        $stmtBom->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmtBom->execute();
        $bomRows = $stmtBom->fetchAll(PDO::FETCH_ASSOC);

        $materialCost = 0.0;
        foreach ($bomRows as $row) {
            $qty    = (float)($row['quantity_per_batch'] ?? 0);
            $scrap  = (float)($row['scrap_percent'] ?? 0);
            $cost   = (float)($row['component_cost'] ?? 0);
            if ($qty <= 0 || $cost <= 0) {
                continue;
            }
            $effectiveQty = $qty * (1 + $scrap / 100.0);
            $materialCost += $effectiveQty * $cost;
        }

        // Custo de operações (rota)
        $sqlOps = "SELECT 
                        io.time_per_batch_hours,
                        io.time_unit,
                        op.default_cost_per_hour
                   FROM inv_item_operations io
                   INNER JOIN inv_operations op ON op.id = io.inv_operation_id
                   WHERE io.inv_item_id = :item_id";
        $stmtOps = $conn->prepare($sqlOps);
        $stmtOps->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmtOps->execute();
        $opRows = $stmtOps->fetchAll(PDO::FETCH_ASSOC);

        $operationsCost = 0.0;
        foreach ($opRows as $row) {
            $rawTime   = (float)($row['time_per_batch_hours'] ?? 0);
            $timeUnit  = strtoupper((string)($row['time_unit'] ?? 'MIN'));
            $costHour  = (float)($row['default_cost_per_hour'] ?? 0);
            if (!in_array($timeUnit, ['MIN', 'H'], true)) {
                $timeUnit = 'MIN';
            }
            $timeHours = $timeUnit === 'H' ? $rawTime : $rawTime / 60.0;
            if ($timeHours <= 0 || $costHour <= 0) {
                continue;
            }
            $operationsCost += $timeHours * $costHour;
        }

        $totalCost = $materialCost + $operationsCost;

        // Atualizar inv_items.average_cost e last_cost com a soma (Lista de materiais + Rota)
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
}

