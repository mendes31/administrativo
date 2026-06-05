<?php

declare(strict_types=1);

use App\adms\Models\Repository\inventory\InvItemOperationsRepository;

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
