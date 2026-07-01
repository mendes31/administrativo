<?php

declare(strict_types=1);

use App\adms\Models\Repository\inventory\InvItemOperationsRepository;

if (!function_exists('invResolveLineTimeMinutes')) {
    /**
     * Tempo efetivo da linha: parcial se informado (≤ operação), senão tempo da operação.
     */
    function invResolveLineTimeMinutes(?float $lineTimeMinutes, float $operationTimeMinutes): float
    {
        if ($lineTimeMinutes !== null && $lineTimeMinutes > 0) {
            if ($operationTimeMinutes > 0) {
                return min($lineTimeMinutes, $operationTimeMinutes);
            }

            return $lineTimeMinutes;
        }

        return max(0.0, $operationTimeMinutes);
    }
}

if (!function_exists('invIsMachineResourceLine')) {
    /**
     * Recurso que gera HM (crit. 3). MO (LABOR) e energia pura não entram.
     */
    function invIsMachineResourceLine(array $line): bool
    {
        if ((int) ($line['inv_production_resource_id'] ?? 0) <= 0) {
            return false;
        }
        $type = strtoupper((string) ($line['resource_type'] ?? 'MACHINE'));

        return !in_array($type, ['LABOR', 'ENERGY'], true);
    }
}

if (!function_exists('invNormalizeLaborLinesForDrivers')) {
    /**
     * Agrupa papéis MO repetidos (mesmo inv_labor_role_id) somando quantidade.
     *
     * @param list<array<string, mixed>> $lines
     * @return list<array<string, mixed>>
     */
    function invNormalizeLaborLinesForDrivers(array $lines): array
    {
        $map = [];
        foreach ($lines as $line) {
            $roleId = (int) ($line['inv_labor_role_id'] ?? 0);
            if ($roleId <= 0) {
                continue;
            }
            if (!isset($map[$roleId])) {
                $map[$roleId] = $line;
                continue;
            }
            $map[$roleId]['qty'] = max(1, (int) ($map[$roleId]['qty'] ?? 1))
                + max(1, (int) ($line['qty'] ?? 1));
        }

        return array_values($map);
    }
}

if (!function_exists('invFilterMachineResourceLines')) {
    /**
     * @param list<array<string, mixed>> $lines
     * @return list<array<string, mixed>>
     */
    function invFilterMachineResourceLines(array $lines): array
    {
        return array_values(array_filter($lines, 'invIsMachineResourceLine'));
    }
}

if (!function_exists('invNormalizeMachineResourceLinesForDrivers')) {
    /**
     * Agrupa equipamentos repetidos somando quantidade (apenas máquina/equip.).
     *
     * @param list<array<string, mixed>> $lines
     * @return list<array<string, mixed>>
     */
    function invNormalizeMachineResourceLinesForDrivers(array $lines): array
    {
        $map = [];
        foreach (invFilterMachineResourceLines($lines) as $line) {
            $resId = (int) ($line['inv_production_resource_id'] ?? 0);
            if ($resId <= 0) {
                continue;
            }
            if (!isset($map[$resId])) {
                $map[$resId] = $line;
                continue;
            }
            $map[$resId]['qty'] = max(1, (int) ($map[$resId]['qty'] ?? 1))
                + max(1, (int) ($line['qty'] ?? 1));
        }

        return array_values($map);
    }
}

if (!function_exists('invOperationDriverHours')) {
    /**
     * HH e HM com suporte a tempo parcial por linha de MO/recurso.
     *
     * HH: soma (tempo_efetivo_h × qtd) por papel MO.
     * HM: soma (tempo_efetivo_h × qtd) apenas por equipamento (não MO/energia).
     * Sem equipamento vinculado → HM = 0.
     *
     * @param list<array<string, mixed>> $laborLines
     * @param list<array<string, mixed>> $resourceLines
     * @return array{labor_hours: float, machine_hours: float}
     */
    function invOperationDriverHours(
        array $laborLines,
        array $resourceLines,
        float $operationTimeMinutes,
        int $operatorsQty = 1
    ): array {
        $laborHours = 0.0;
        $machineHours = 0.0;

        $laborLines = invNormalizeLaborLinesForDrivers($laborLines);
        $machineLines = invNormalizeMachineResourceLinesForDrivers($resourceLines);

        foreach ($laborLines as $line) {
            $qty = max(1, (int) ($line['qty'] ?? 1));
            $lineMin = invResolveLineTimeMinutes(
                isset($line['line_time_minutes']) && $line['line_time_minutes'] !== null && $line['line_time_minutes'] !== ''
                    ? (float) $line['line_time_minutes']
                    : null,
                $operationTimeMinutes
            );
            $laborHours += ($lineMin / 60.0) * $qty;
        }

        if ($laborLines === [] && $operationTimeMinutes > 0 && $operatorsQty > 0) {
            $laborHours = ($operationTimeMinutes / 60.0) * $operatorsQty;
        }

        foreach ($machineLines as $line) {
            $qty = max(1, (int) ($line['qty'] ?? 1));
            $lineMin = invResolveLineTimeMinutes(
                isset($line['line_time_minutes']) && $line['line_time_minutes'] !== null && $line['line_time_minutes'] !== ''
                    ? (float) $line['line_time_minutes']
                    : null,
                $operationTimeMinutes
            );
            if ($lineMin > 0) {
                $machineHours += ($lineMin / 60.0) * $qty;
            }
        }

        return [
            'labor_hours' => round($laborHours, 6),
            'machine_hours' => round($machineHours, 6),
        ];
    }
}

if (!function_exists('invSplitResourceCostsPerMinute')) {
    /**
     * @param list<array<string, mixed>> $resourceLines
     * @param array<string, mixed> $op
     * @return array{sap_labor: float, equipment: float, machine: float, energy: float}
     */
    function invSplitResourceCostsPerMinute(array $resourceLines, array $op): array
    {
        $sapLabor = 0.0;
        $equipment = 0.0;
        $machine = 0.0;
        $energy = 0.0;

        foreach ($resourceLines as $line) {
            $qty = max(0, (int) ($line['qty'] ?? 0));
            if ($qty <= 0) {
                continue;
            }
            $lineMachine = $qty * max(0, (float) ($line['machine_cost_per_min'] ?? 0));
            $lineEnergy = $qty * max(0, (float) ($line['energy_cost_per_min'] ?? 0));
            $machine += $lineMachine;
            $energy += $lineEnergy;
            $type = strtoupper((string) ($line['resource_type'] ?? 'MACHINE'));
            if ($type === 'LABOR') {
                $sapLabor += $lineMachine + $lineEnergy;
            } else {
                $equipment += $lineMachine + $lineEnergy;
            }
        }

        if ($resourceLines === []) {
            $notes = mb_strtoupper((string) ($op['notes'] ?? ''), 'UTF-8');
            $headerMachine = max(0, (float) ($op['machine_cost_per_min'] ?? 0));
            $headerEnergy = max(0, (float) ($op['energy_cost_per_min'] ?? 0));
            $headerLabor = max(0, (float) ($op['labor_cost_per_min'] ?? 0));
            $machine = $headerMachine;
            $energy = $headerEnergy;
            if (str_contains($notes, 'RECURSO SAP')) {
                $sapLabor = $headerLabor + $headerMachine + $headerEnergy;
                $equipment = 0.0;
                $machine = 0.0;
                $energy = 0.0;
            } else {
                $equipment = $headerMachine + $headerEnergy;
            }
        }

        return [
            'sap_labor' => round($sapLabor, 6),
            'equipment' => round($equipment, 6),
            'machine' => round($machine, 6),
            'energy' => round($energy, 6),
        ];
    }
}

if (!function_exists('invIsRateioExcludedOperation')) {
    /**
     * Operações administrativas sem tempo produtivo (não entram em HH/HM de rateio).
     */
    function invIsRateioExcludedOperation(string $operationName): bool
    {
        $upper = mb_strtoupper(trim($operationName), 'UTF-8');
        if ($upper === '') {
            return true;
        }

        return str_contains($upper, 'LIBERAR SALA')
            || str_contains($upper, 'RENDIMENTO');
    }
}

if (!function_exists('invResolveRateioOperationMinutes')) {
    /**
     * Tempo efetivo por lote para drivers de rateio (planilha Tiaraju).
     *
     * - Rota consolidada / tempos em horas: usa o tempo da operação uma vez por etapa.
     * - Rota SAP em minutos: usa o valor cadastrado (referência BEAS; alinhar via rota consolidada).
     */
    function invResolveRateioOperationMinutes(array $op, float $batchSize): float
    {
        $raw = (float) ($op['time_per_batch_hours'] ?? 0);
        if ($raw <= 0) {
            return 0.0;
        }
        $unit = strtoupper((string) ($op['time_unit'] ?? 'MIN'));
        if (!in_array($unit, ['MIN', 'H'], true)) {
            $unit = 'MIN';
        }

        return $unit === 'H' ? $raw * 60.0 : $raw;
    }
}

if (!function_exists('invAggregateRouteRateioDrivers')) {
    /**
     * Drivers CFIX crit. 2 (HH) e crit. 3 (HM) alinhados à planilha:
     * - HM/lote = Σ tempo de etapa (h), sem multiplicar MO.
     * - HH/lote = HM/lote × eficiência de produção do período (Pasta 5).
     *
     * @param list<array<string, mixed>> $operations
     * @return array{rateio_labor_hours: float, rateio_machine_hours: float}
     */
    function invAggregateRouteRateioDrivers(
        array $operations,
        float $batchSize,
        ?float $productionEfficiencyRatio = null
    ): array {
        $machineHours = 0.0;
        foreach ($operations as $op) {
            $name = (string) ($op['operation_name'] ?? '');
            if (invIsRateioExcludedOperation($name)) {
                continue;
            }
            $minutes = invResolveRateioOperationMinutes($op, $batchSize);
            if ($minutes > 0) {
                $machineHours += $minutes / 60.0;
            }
        }
        $eff = ($productionEfficiencyRatio !== null && $productionEfficiencyRatio > 0)
            ? $productionEfficiencyRatio
            : 1.0;
        $laborHours = $machineHours * $eff;

        return [
            'rateio_labor_hours' => round($laborHours, 6),
            'rateio_machine_hours' => round($machineHours, 6),
        ];
    }
}

if (!function_exists('invOperationMetrics')) {
    /**
     * @param array<string, mixed> $op
     * @return array<string, float>
     */
    function invOperationMetrics(array $op): array
    {
        $timeValue = (float) ($op['time_per_batch_hours'] ?? 0);
        $timeUnit = strtoupper((string) ($op['time_unit'] ?? 'MIN'));
        if (!in_array($timeUnit, ['MIN', 'H'], true)) {
            $timeUnit = 'MIN';
        }
        $timeMinutes = $timeUnit === 'H' ? $timeValue * 60.0 : $timeValue;

        $laborLines = $op['labor_lines'] ?? [];
        $manualLaborPerMin = InvItemOperationsRepository::sumLaborCostPerMinute($laborLines);

        $resourceLines = $op['resource_lines'] ?? [];
        $split = invSplitResourceCostsPerMinute($resourceLines, $op);
        $sapLaborPerMin = (float) ($split['sap_labor'] ?? 0);
        $equipmentPerMin = (float) ($split['equipment'] ?? 0);
        $machinePerMin = (float) ($split['machine'] ?? 0);
        $energyPerMin = (float) ($split['energy'] ?? 0);

        $costHour = (float) ($op['operation_cost_per_hour'] ?? 0);
        $costPerMin = $manualLaborPerMin + $sapLaborPerMin + $equipmentPerMin;
        $lineCost = 0.0;
        if ($timeMinutes > 0) {
            if ($costPerMin > 0) {
                $lineCost = $timeMinutes * $costPerMin;
            } elseif ($costHour > 0) {
                $lineCost = ($timeMinutes / 60.0) * $costHour;
            }
        }

        return [
            'time_minutes' => round($timeMinutes, 6),
            'manual_labor_per_min' => round($manualLaborPerMin, 6),
            'sap_labor_per_min' => round($sapLaborPerMin, 6),
            'equipment_per_min' => round($equipmentPerMin, 6),
            'labor_mo_per_min' => round($manualLaborPerMin, 6),
            'machine_per_min' => round($machinePerMin, 6),
            'energy_per_min' => round($energyPerMin, 6),
            'cost_per_min' => round($costPerMin, 6),
            'line_cost' => round($lineCost, 6),
            'cost_hour' => $costHour,
        ];
    }
}

