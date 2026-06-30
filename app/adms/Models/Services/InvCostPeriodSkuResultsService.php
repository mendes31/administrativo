<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;

/**
 * Resultados consolidados por SKU no período (Fase E): CVAR sim., CFIX, energia, custo pleno.
 * Leitura via snapshot persistido ({@see InvCostPeriodSnapshotService}).
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

        $snapshotService = new InvCostPeriodSnapshotService();
        if (!$snapshotService->hasSnapshot($periodId)) {
            $snapshotService->recalculate($periodId);
        }

        return $snapshotService->listForPeriod($periodId, $filter);
    }
}
