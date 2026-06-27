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
    public function computeKwhFromOperationRows(array $operationRows): array
    {
        $kwhPerBatch = 0.0;
        $details = [];

        foreach ($operationRows as $op) {
            $rawTime = (float)($op['time_per_batch_hours'] ?? 0);
            $timeUnit = strtoupper((string)($op['time_unit'] ?? 'MIN'));
            $timeMinutes = $timeUnit === 'H' ? $rawTime * 60.0 : $rawTime;
            $timeHours = $timeMinutes / 60.0;
            if ($timeHours <= 0) {
                continue;
            }

            $resourceLines = is_array($op['resource_lines'] ?? null) ? $op['resource_lines'] : [];
            $hasMachineDriver = $resourceLines !== []
                || (float)($op['machine_cost_per_min'] ?? 0) > 0
                || (float)($op['energy_cost_per_min'] ?? 0) > 0;
            if (!$hasMachineDriver) {
                continue;
            }

            $opKw = 0.0;
            foreach ($resourceLines as $res) {
                $qty = max(1, (int)($res['qty'] ?? 1));
                $opKw += $qty * max(0.0, (float)($res['power_kw'] ?? 0));
            }
            if ($opKw <= 0) {
                continue;
            }

            $opKwh = $timeHours * $opKw;
            $kwhPerBatch += $opKwh;
            $details[] = [
                'operation_code' => (string)($op['operation_code'] ?? ''),
                'operation_name' => (string)($op['operation_name'] ?? ''),
                'time_hours' => round($timeHours, 6),
                'power_kw' => round($opKw, 6),
                'kwh' => round($opKwh, 6),
            ];
        }

        return [
            'kwh_per_batch' => round($kwhPerBatch, 6),
            'operations' => $details,
        ];
    }

    public function computeKwhPerBatchForItem(int $itemId): float
    {
        if ($itemId <= 0) {
            return 0.0;
        }

        $opsRepo = new InvItemOperationsRepository();
        $operations = $opsRepo->getByItem($itemId);

        return $this->computeKwhFromOperationRows($operations)['kwh_per_batch'];
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
            $operationRows = (new InvItemOperationsRepository())->getByItem($itemId);
        }

        $kwhData = $this->computeKwhFromOperationRows($operationRows);
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
     * Driver critério 7 (R$) a partir do HM do período.
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

        $kwhBatch = $this->computeKwhPerBatchForItem($itemId);
        if ($kwhBatch <= 0) {
            return 0.0;
        }

        $kwhPeriod = $kwhBatch * ($hmPeriod / $hmPerBatch);

        return $kwhTariff > 0
            ? round($kwhPeriod * $kwhTariff, 6)
            : round($kwhPeriod, 6);
    }
}
