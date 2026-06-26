<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Soma kWh direto (HM × kW) agregado por período — base para critério 7 / fatia direto.
 */
class InvCostEnergyDriversService
{
    /**
     * @param list<string>|null $warehouseCodes
     */
    public function sumDirectKwhByPeriod(int $periodId, ?array $warehouseCodes = null): float
    {
        if ($periodId <= 0) {
            return 0.0;
        }

        $criteria = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId, $warehouseCodes);
        $tariff = (float)($criteria['period']['kwh_tariff'] ?? 0);
        $total = 0.0;

        foreach ($criteria['items'] ?? [] as $row) {
            $driver7 = (float)($row['driver_7'] ?? 0);
            if ($driver7 <= 0) {
                continue;
            }
            if ($tariff > 0) {
                $total += $driver7 / $tariff;
            } else {
                $total += $driver7;
            }
        }

        return round($total, 4);
    }
}
