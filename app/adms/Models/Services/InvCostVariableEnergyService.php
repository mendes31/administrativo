<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\InvCostProductionLineHelper;
use App\adms\Models\Repository\inventory\InvItemOperationsRepository;

/**
 * CVAR energia elétrica direta: kWh = HM × kW (por operação) × tarifa do período.
 */
class InvCostVariableEnergyService
{
    /**
     * @param list<array<string, mixed>> $operationRows Linhas com resource_lines (power_kw) e tempos.
     * @return array{
     *   kwh_per_batch: float,
     *   operations: list<array{operation_code: string, operation_name: string, time_hours: float, power_kw: float, kwh: float}>
     * }
     */
    public function computeKwhFromOperationRows(array $operationRows, float $kwhTariff = 0.0): array
    {
        $kwhPerBatch = 0.0;
        $details = [];

        require_once __DIR__ . '/../../Views/inventory/partials/operation_metrics.php';

        foreach ($operationRows as $op) {
            $rawTime = (float)($op['time_per_batch_hours'] ?? 0);
            $timeUnit = strtoupper((string)($op['time_unit'] ?? 'MIN'));
            $timeMinutes = $timeUnit === 'H' ? $rawTime * 60.0 : $rawTime;
            if ($timeMinutes <= 0) {
                continue;
            }

            $resourceLines = is_array($op['resource_lines'] ?? null) ? $op['resource_lines'] : [];
            $opKwh = 0.0;
            $opKwTotal = 0.0;
            $opHoursTotal = 0.0;

            if ($resourceLines !== []) {
                foreach ($resourceLines as $res) {
                    if ($this->isLaborResource($res)) {
                        continue;
                    }
                    $resId = (int)($res['inv_production_resource_id'] ?? 0);
                    if ($resId <= 0) {
                        continue;
                    }
                    $qty = max(1, (int)($res['qty'] ?? 1));
                    $lineMin = invResolveLineTimeMinutes(
                        isset($res['line_time_minutes']) && $res['line_time_minutes'] !== null && $res['line_time_minutes'] !== ''
                            ? (float)$res['line_time_minutes']
                            : null,
                        $timeMinutes
                    );
                    $lineHours = $lineMin / 60.0;
                    if ($lineHours <= 0) {
                        continue;
                    }
                    $resKw = $qty * max(0.0, (float)($res['power_kw'] ?? 0));
                    $energyEcpm = $qty * max(0.0, (float)($res['energy_cost_per_min'] ?? 0));
                    if ($resKw > 0) {
                        $opKwh += $lineHours * $resKw;
                        $opKwTotal += $resKw;
                        $opHoursTotal += $lineHours;
                    } elseif ($kwhTariff > 0 && $energyEcpm > 0) {
                        $lineKwh = ($lineMin * $energyEcpm) / $kwhTariff;
                        $opKwh += $lineKwh;
                        $opKwTotal += $lineHours > 0 ? $lineKwh / $lineHours : 0.0;
                        $opHoursTotal += $lineHours;
                    }
                }
            }

            if ($opKwh <= 0) {
                $timeHours = $timeMinutes / 60.0;
                $opKw = $this->resolveOperationPowerKw($op);
                $energyEcpm = $this->resolveOperationEnergyEcpmPerMin($op);
                if ($opKw <= 0 && $energyEcpm <= 0) {
                    continue;
                }
                if ($opKw > 0) {
                    $opKwh = $timeHours * $opKw;
                    $opKwTotal = $opKw;
                    $opHoursTotal = $timeHours;
                } elseif ($kwhTariff > 0) {
                    $opKwh = ($timeMinutes * $energyEcpm) / $kwhTariff;
                    $opKwTotal = $timeHours > 0 ? $opKwh / $timeHours : 0.0;
                    $opHoursTotal = $timeHours;
                } else {
                    continue;
                }
            }

            $kwhPerBatch += $opKwh;
            $details[] = [
                'operation_code' => (string)($op['operation_code'] ?? ''),
                'operation_name' => (string)($op['operation_name'] ?? ''),
                'time_hours' => round($opHoursTotal > 0 ? $opHoursTotal : ($timeMinutes / 60.0), 6),
                'power_kw' => round($opKwTotal, 6),
                'kwh' => round($opKwh, 6),
            ];
        }

        return [
            'kwh_per_batch' => round($kwhPerBatch, 6),
            'operations' => $details,
        ];
    }

    public function computeKwhPerBatchForItem(int $itemId, float $kwhTariff = 0.0): float
    {
        if ($itemId <= 0) {
            return 0.0;
        }

        $opsRepo = new InvItemOperationsRepository();
        $operations = $opsRepo->getByItemForCosting($itemId);

        return $this->computeKwhFromOperationRows($operations, $kwhTariff)['kwh_per_batch'];
    }

    /**
     * @param list<array<string, mixed>>|null $operationRows
     * @return array{
     *   kwh_per_batch: float,
     *   kwh_per_unit: float,
     *   cost_batch: float,
     *   cost_unit: float,
     *   kwh_tariff: float,
     *   operations: list<array<string, mixed>>
     * }
     */
    public function computeCvarEnergy(
        int $itemId,
        float $batchSize,
        float $kwhTariff,
        ?array $operationRows = null,
        ?string $productionLine = null
    ): array {
        $empty = [
            'kwh_per_batch' => 0.0,
            'kwh_per_unit' => 0.0,
            'cost_batch' => 0.0,
            'cost_unit' => 0.0,
            'kwh_tariff' => max(0.0, $kwhTariff),
            'operations' => [],
        ];

        if ($itemId <= 0 || $kwhTariff <= 0 || $batchSize <= 0) {
            return $empty;
        }

        if (!InvCostProductionLineHelper::isEligibleForDirectEnergy($productionLine)) {
            return $empty;
        }

        if ($operationRows === null) {
            $operationRows = (new InvItemOperationsRepository())->getByItemForCosting($itemId);
        }

        $kwhData = $this->computeKwhFromOperationRows($operationRows, $kwhTariff);
        $kwhBatch = (float)($kwhData['kwh_per_batch'] ?? 0);
        if ($kwhBatch <= 0) {
            return $empty;
        }

        $costBatch = round($kwhBatch * $kwhTariff, 6);

        return [
            'kwh_per_batch' => $kwhBatch,
            'kwh_per_unit' => round($kwhBatch / $batchSize, 6),
            'cost_batch' => $costBatch,
            'cost_unit' => round($costBatch / $batchSize, 6),
            'kwh_tariff' => $kwhTariff,
            'operations' => $kwhData['operations'],
        ];
    }

    /**
     * Driver critério 7 (R$ ou kWh) a partir da rota (kW / custo EE) e HM do período.
     * Apenas linha TIARAJU (produção interna); TERCEIRO fica com driver zero.
     *
     * @param array<string, mixed>|null $periodItem
     */
    public function computePeriodEnergyDriver(
        int $itemId,
        float $hmPeriod,
        float $hmPerBatch,
        float $kwhTariff,
        ?array $periodItem = null
    ): float {
        if (!InvCostProductionLineHelper::isPeriodItemEligibleForDirectEnergy($periodItem)) {
            return 0.0;
        }

        if ($itemId <= 0 || $hmPeriod <= 0 || $hmPerBatch <= 0) {
            return 0.0;
        }

        $kwhBatch = $this->computeKwhPerBatchForItem($itemId, $kwhTariff);
        if ($kwhBatch <= 0) {
            return 0.0;
        }

        $kwhPeriod = $kwhBatch * ($hmPeriod / $hmPerBatch);

        return $this->driverFromKwh($kwhPeriod, $kwhTariff);
    }

    /**
     * Fallback crit. 7: HM produtivo × kWh/HM observado na rota (ou 1 kWh/HM).
     */
    public function computeHmFallbackDriver(float $hmPeriod, float $kwhPerHm, float $kwhTariff): float
    {
        if ($hmPeriod <= 0 || $kwhPerHm <= 0) {
            return 0.0;
        }

        return $this->driverFromKwh($hmPeriod * $kwhPerHm, $kwhTariff);
    }

    public function driverFromKwh(float $kwh, float $kwhTariff): float
    {
        if ($kwh <= 0) {
            return 0.0;
        }

        return $kwhTariff > 0
            ? round($kwh * $kwhTariff, 6)
            : round($kwh, 6);
    }

    /**
     * @param array<string, mixed> $op
     */
    private function resolveOperationPowerKw(array $op): float
    {
        $resourceLines = is_array($op['resource_lines'] ?? null) ? $op['resource_lines'] : [];
        $opKw = 0.0;

        foreach ($resourceLines as $res) {
            if ($this->isLaborResource($res)) {
                continue;
            }
            $qty = max(1, (int)($res['qty'] ?? 1));
            $opKw += $qty * max(0.0, (float)($res['power_kw'] ?? 0));
        }

        return $opKw;
    }

    /**
     * Custo EE/min de equipamentos (exclui MO SAP).
     *
     * @param array<string, mixed> $op
     */
    private function resolveOperationEnergyEcpmPerMin(array $op): float
    {
        $resourceLines = is_array($op['resource_lines'] ?? null) ? $op['resource_lines'] : [];
        $energy = 0.0;
        $hasEquipment = false;

        foreach ($resourceLines as $res) {
            if ($this->isLaborResource($res)) {
                continue;
            }
            $hasEquipment = true;
            $qty = max(0, (int)($res['qty'] ?? 0));
            if ($qty <= 0) {
                continue;
            }
            $energy += $qty * max(0.0, (float)($res['energy_cost_per_min'] ?? 0));
        }

        if (!$hasEquipment) {
            return max(0.0, (float)($op['energy_cost_per_min'] ?? 0));
        }

        if ($energy > 0) {
            return $energy;
        }

        $headerEnergy = max(0.0, (float)($op['energy_cost_per_min'] ?? 0));
        if ($headerEnergy > 0) {
            return $headerEnergy;
        }

        return 0.0;
    }

    /**
     * @param array<string, mixed> $res
     */
    private function isLaborResource(array $res): bool
    {
        return strtoupper((string)($res['resource_type'] ?? 'MACHINE')) === 'LABOR';
    }
}
