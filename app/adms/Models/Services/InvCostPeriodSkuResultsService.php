<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;

/**
 * Resultados consolidados por SKU no período (Fase E): CVAR sim., CFIX, energia, custo pleno.
 */
class InvCostPeriodSkuResultsService
{
    /**
     * @param list<string>|null $warehouseCodes
     * @param array<string, mixed>|null $productionAggregation
     * @param array<string, mixed>|null $criterionAggregation
     * @return list<array<string, mixed>>
     */
    public function listForPeriod(
        int $periodId,
        ?array $warehouseCodes = null,
        ?string $filter = null,
        ?array $productionAggregation = null,
        ?array $criterionAggregation = null
    ): array {
        if ($periodId <= 0) {
            return [];
        }

        $period = (new InvCostPeriodsRepository())->getOne($periodId);
        if ($period === false) {
            return [];
        }

        $productionRows = (new InvCostPeriodProductionItemsService())->listForPeriod(
            periodId: $periodId,
            warehouseCodes: $warehouseCodes,
            filter: $filter,
            productionAggregation: $productionAggregation,
            criterionAggregation: $criterionAggregation
        );
        if ($productionRows === []) {
            return [];
        }

        $allocation = (new InvCostFixedAllocationEngine())->allocateByPeriod($periodId, $warehouseCodes);
        $byItemCfix = $allocation['by_item'] ?? [];
        $tariff = (float)($period['kwh_tariff'] ?? 0);

        $results = [];
        foreach ($productionRows as $row) {
            $itemId = (int)($row['inv_item_id'] ?? 0);
            $qty = (float)($row['total_qty'] ?? 0);
            $effRatio = isset($row['efficiency_ratio']) ? (float)$row['efficiency_ratio'] : null;

            $cvarSimUnit = null;
            $cvarEnergyUnit = null;
            $cvarMpUnit = null;
            $cfixTotal = 0.0;
            $cfixUnit = null;
            $fullCostUnit = null;

            if ($itemId > 0) {
                $scenario = [];
                if ($effRatio !== null && $effRatio > 0) {
                    $scenario['production_efficiency_ratio'] = $effRatio;
                }
                if ($tariff > 0) {
                    $scenario['kwh_tariff'] = $tariff;
                }

                $breakdown = InventoryCostService::calculateBreakdown($itemId, $scenario);
                $cvarSimUnit = (float)($breakdown['simulated_total'] ?? 0);
                $cvarEnergyUnit = (float)($breakdown['simulated_cvar_energy_cost'] ?? 0);
                $cvarMpUnit = (float)($breakdown['simulated_cvar_mp_cost'] ?? 0);

                $cfixTotal = (float)($byItemCfix[$itemId]['cfix_total'] ?? 0);
                $cfixUnit = ($cfixTotal > 0 && $qty > 0) ? round($cfixTotal / $qty, 6) : ($cfixTotal > 0 ? null : 0.0);
                $fullCostUnit = round($cvarSimUnit + $cvarEnergyUnit + (float)($cfixUnit ?? 0), 6);
            }

            $results[] = array_merge($row, [
                'cvar_sim_unit' => $cvarSimUnit,
                'cvar_mp_unit' => $cvarMpUnit,
                'cvar_energy_unit' => $cvarEnergyUnit,
                'cfix_total' => round($cfixTotal, 4),
                'cfix_unit' => $cfixUnit,
                'full_cost_unit' => $fullCostUnit,
                'kwh_tariff' => $tariff > 0 ? $tariff : null,
            ]);
        }

        return $results;
    }
}
