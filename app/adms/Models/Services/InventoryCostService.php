<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
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
        $totalCost = (float)($breakdown['base_total_batch'] ?? $breakdown['base_total'] ?? 0);

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
     *   global_adjust_pct?: float,
     *   standard_batch_size?: float
     * } $scenario Percentuais de ajuste (ex.: 5 = +5%, -10 = -10%)
     * @return array<string, mixed>
     */
    public static function calculateBreakdown(int $itemId, array $scenario = []): array
    {
        $empty = [
            'item_id' => $itemId,
            'standard_batch_size' => 1.0,
            'materials' => [],
            'material_groups' => [],
            'operations' => [],
            'material_cost' => 0.0,
            'operations_cost' => 0.0,
            'route_labor_cost' => 0.0,
            'route_manual_labor_cost' => 0.0,
            'route_sap_labor_cost' => 0.0,
            'route_equipment_cost' => 0.0,
            'route_resources_cost' => 0.0,
            'labor_hours' => 0.0,
            'machine_hours' => 0.0,
            'base_total' => 0.0,
            'simulated_material_cost' => 0.0,
            'simulated_operations_cost' => 0.0,
            'simulated_material_groups' => [],
            'simulated_route_manual_labor_cost' => 0.0,
            'simulated_route_sap_labor_cost' => 0.0,
            'simulated_route_equipment_cost' => 0.0,
            'simulated_route_resources_cost' => 0.0,
            'simulated_total' => 0.0,
            'scenario' => self::normalizeScenario($scenario),
        ];

        if ($itemId <= 0) {
            return $empty;
        }

        $conn = self::getStaticConnection();
        $scenario = $empty['scenario'];
        $customBomRows = $scenario['custom_bom_rows'] ?? null;
        $customOpRows = $scenario['custom_operation_rows'] ?? null;
        $useCustomBom = is_array($customBomRows);
        $useCustomOps = is_array($customOpRows);

        $stmtItem = $conn->prepare('SELECT standard_batch_size FROM inv_items WHERE id = :item_id LIMIT 1');
        $stmtItem->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmtItem->execute();
        $itemRow = $stmtItem->fetch(PDO::FETCH_ASSOC) ?: [];
        $scenarioBatch = isset($scenario['standard_batch_size']) && (float)$scenario['standard_batch_size'] > 0
            ? self::normalizeBatchSize((float)$scenario['standard_batch_size'])
            : null;
        $batchSize = $scenarioBatch ?? self::normalizeBatchSize((float)($itemRow['standard_batch_size'] ?? 1));

        if ($useCustomBom) {
            $bomRows = $customBomRows;
        } else {
            $sqlBom = "SELECT 
                        b.line_source,
                        b.quantity_per_batch,
                        b.scrap_percent,
                        b.manual_description,
                        b.manual_component_type,
                        b.manual_unit,
                        b.manual_unit_cost,
                        i.code AS component_code,
                        i.description AS component_description,
                        i.average_cost AS component_cost,
                        c.name AS component_category
                   FROM inv_item_bom b
                   LEFT JOIN inv_items i ON i.id = b.component_item_id
                   LEFT JOIN inv_categories c ON c.id = i.inv_category_id
                   WHERE b.inv_item_id = :item_id
                   ORDER BY b.line_source ASC, c.name ASC, i.description ASC, b.id ASC";
            $stmtBom = $conn->prepare($sqlBom);
            $stmtBom->bindValue(':item_id', $itemId, PDO::PARAM_INT);
            $stmtBom->execute();
            $bomRows = $stmtBom->fetchAll(PDO::FETCH_ASSOC);
        }

        $materials = [];
        $materialCost = 0.0;
        $cvarMpCostBatch = 0.0;
        $cvarMaeCostBatch = 0.0;
        $materialGroupsMap = [];
        foreach ($bomRows as $row) {
            $line = self::computeMaterialLine($row);
            $materials[] = $line;
            $lineCostBatch = (float)($line['line_cost_batch'] ?? $line['line_cost'] ?? 0);
            $materialCost += $lineCostBatch;
            $groupName = (string)($line['group_name'] ?? 'Outros');
            if (!isset($materialGroupsMap[$groupName])) {
                $materialGroupsMap[$groupName] = 0.0;
            }
            $materialGroupsMap[$groupName] += $lineCostBatch;
            if ($groupName === 'Matéria Prima') {
                $cvarMpCostBatch += $lineCostBatch;
            } elseif ($groupName === 'Embalagens') {
                $cvarMaeCostBatch += $lineCostBatch;
            }
        }
        $materialGroups = self::buildMaterialGroupsList($materialGroupsMap);

        if ($useCustomOps) {
            $opRows = $customOpRows;
        } else {
            $sqlOps = "SELECT 
                        io.id,
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
        }

        $opsRepo = new InvItemOperationsRepository();
        if (!$useCustomOps) {
            $opIds = array_map(static fn(array $r): int => (int)($r['id'] ?? 0), $opRows);
            $laborByOp = $opsRepo->getLaborLinesByOperationIds($opIds);
            $resourceByOp = $opsRepo->getResourceLinesByOperationIds($opIds);
        } else {
            $laborByOp = [];
            $resourceByOp = [];
        }

        $operations = [];
        $operationsCost = 0.0;
        $routeManualLaborCost = 0.0;
        $routeSapLaborCost = 0.0;
        $routeEquipmentCost = 0.0;
        $laborHours = 0.0;
        $machineHours = 0.0;
        foreach ($opRows as $row) {
            if (!$useCustomOps) {
                $opId = (int)($row['id'] ?? 0);
                $row['labor_lines'] = $laborByOp[$opId] ?? [];
                $row['resource_lines'] = $resourceByOp[$opId] ?? [];
            } else {
                $row['labor_lines'] = is_array($row['labor_lines'] ?? null) ? $row['labor_lines'] : [];
                $row['resource_lines'] = is_array($row['resource_lines'] ?? null) ? $row['resource_lines'] : [];
            }
            $line = self::computeOperationLine($row);
            $operations[] = $line;
            $operationsCost += (float)$line['line_cost'];
            $routeManualLaborCost += (float)($line['manual_labor_line_cost'] ?? 0);
            $routeSapLaborCost += (float)($line['sap_labor_line_cost'] ?? 0);
            $routeEquipmentCost += (float)($line['equipment_line_cost'] ?? 0);
            $laborHours += (float)($line['labor_hours'] ?? 0);
            $machineHours += (float)($line['machine_hours'] ?? 0);
        }
        $routeLaborCost = $routeManualLaborCost;
        $routeResourcesCost = $routeSapLaborCost + $routeEquipmentCost;

        $materialCostBatch = $materialCost;
        $operationsCostBatch = $operationsCost;
        $baseTotalBatch = $materialCostBatch + $operationsCostBatch;

        $materials = self::applyBatchSizeToMaterialLines($materials, $batchSize);
        $operations = self::applyBatchSizeToOperationLines($operations, $batchSize);
        $materialGroups = self::applyBatchSizeToMaterialGroups($materialGroups, $batchSize);

        $materialCost = $materialCostBatch / $batchSize;
        $operationsCost = $operationsCostBatch / $batchSize;
        $routeManualLaborCost /= $batchSize;
        $routeSapLaborCost /= $batchSize;
        $routeEquipmentCost /= $batchSize;
        $routeLaborCost = $routeManualLaborCost;
        $routeResourcesCost = $routeSapLaborCost + $routeEquipmentCost;

        $baseTotal = $materialCost + $operationsCost;
        $materialFactor = 1 + ((float)$scenario['material_adjust_pct'] / 100.0);
        $operationsFactor = 1 + ((float)$scenario['operations_adjust_pct'] / 100.0);
        $globalFactor = 1 + ((float)$scenario['global_adjust_pct'] / 100.0);

        $simMaterial = $materialCost * $materialFactor;
        $simOperations = $operationsCost * $operationsFactor;
        $simTotal = ($simMaterial + $simOperations) * $globalFactor;
        $simMaterialGroups = array_map(
            static fn(array $group): array => [
                'group_name' => (string)($group['group_name'] ?? 'Outros'),
                'line_cost' => round((float)($group['line_cost'] ?? 0) * $materialFactor, 6),
                'line_cost_batch' => round((float)($group['line_cost_batch'] ?? 0) * $materialFactor, 6),
            ],
            $materialGroups
        );
        $simRouteManualLabor = $routeManualLaborCost * $operationsFactor;
        $simRouteSapLabor = $routeSapLaborCost * $operationsFactor;
        $simRouteEquipment = $routeEquipmentCost * $operationsFactor;
        $simRouteLabor = $simRouteManualLabor;
        $simRouteResources = $simRouteSapLabor + $simRouteEquipment;

        $routeManualLaborCostBatch = $routeManualLaborCost * $batchSize;
        $routeSapLaborCostBatch = $routeSapLaborCost * $batchSize;
        $routeEquipmentCostBatch = $routeEquipmentCost * $batchSize;
        $simMaterialBatch = $simMaterial * $batchSize;
        $simOperationsBatch = $simOperations * $batchSize;
        $simTotalBatch = $simTotal * $batchSize;

        $cvarMpCost = $cvarMpCostBatch / $batchSize;
        $cvarMaeCost = $cvarMaeCostBatch / $batchSize;
        $simCvarMp = $cvarMpCost * $materialFactor * $globalFactor;
        $simCvarMae = $cvarMaeCost * $materialFactor * $globalFactor;

        return [
            'item_id' => $itemId,
            'standard_batch_size' => $batchSize,
            'materials' => $materials,
            'material_groups' => $materialGroups,
            'operations' => $operations,
            'material_cost' => round($materialCost, 6),
            'operations_cost' => round($operationsCost, 6),
            'material_cost_batch' => round($materialCostBatch, 6),
            'operations_cost_batch' => round($operationsCostBatch, 6),
            'base_total_batch' => round($baseTotalBatch, 6),
            'route_labor_cost' => round($routeLaborCost, 6),
            'route_manual_labor_cost' => round($routeManualLaborCost, 6),
            'route_sap_labor_cost' => round($routeSapLaborCost, 6),
            'route_equipment_cost' => round($routeEquipmentCost, 6),
            'route_resources_cost' => round($routeResourcesCost, 6),
            'route_manual_labor_cost_batch' => round($routeManualLaborCostBatch, 6),
            'route_sap_labor_cost_batch' => round($routeSapLaborCostBatch, 6),
            'route_equipment_cost_batch' => round($routeEquipmentCostBatch, 6),
            'labor_hours' => round($laborHours, 6),
            'machine_hours' => round($machineHours, 6),
            'base_total' => round($baseTotal, 6),
            'simulated_material_cost' => round($simMaterial, 6),
            'simulated_operations_cost' => round($simOperations, 6),
            'simulated_material_groups' => $simMaterialGroups,
            'simulated_route_labor_cost' => round($simRouteLabor, 6),
            'simulated_route_manual_labor_cost' => round($simRouteManualLabor, 6),
            'simulated_route_sap_labor_cost' => round($simRouteSapLabor, 6),
            'simulated_route_equipment_cost' => round($simRouteEquipment, 6),
            'simulated_route_resources_cost' => round($simRouteResources, 6),
            'simulated_total' => round($simTotal, 6),
            'simulated_material_cost_batch' => round($simMaterialBatch, 6),
            'simulated_operations_cost_batch' => round($simOperationsBatch, 6),
            'simulated_total_batch' => round($simTotalBatch, 6),
            'cvar_mp_cost' => round($cvarMpCost, 6),
            'cvar_mae_cost' => round($cvarMaeCost, 6),
            'cvar_mp_cost_batch' => round($cvarMpCostBatch, 6),
            'cvar_mae_cost_batch' => round($cvarMaeCostBatch, 6),
            'simulated_cvar_mp_cost' => round($simCvarMp, 6),
            'simulated_cvar_mae_cost' => round($simCvarMae, 6),
            'simulated_cvar_mp_cost_batch' => round($simCvarMp * $batchSize, 6),
            'simulated_cvar_mae_cost_batch' => round($simCvarMae * $batchSize, 6),
            'simulated_route_manual_labor_cost_batch' => round($simRouteManualLabor * $batchSize, 6),
            'simulated_route_sap_labor_cost_batch' => round($simRouteSapLabor * $batchSize, 6),
            'simulated_route_equipment_cost_batch' => round($simRouteEquipment * $batchSize, 6),
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
        $lineSource = (string)($row['line_source'] ?? 'catalog');

        if ($lineSource === 'manual') {
            $cost = max(0.0, (float)($row['manual_unit_cost'] ?? 0));
            $type = strtoupper(trim((string)($row['manual_component_type'] ?? 'OTHER')));
            $groupName = self::resolveManualComponentGroupName($type);
            $description = trim((string)($row['manual_description'] ?? ''));
            $code = 'MANUAL';
            $category = $type;
        } else {
            $cost = (float)($row['component_cost'] ?? 0);
            $category = trim((string)($row['component_category'] ?? ''));
            $groupName = self::resolveMaterialGroupName($category);
            $code = (string)($row['component_code'] ?? '');
            $description = (string)($row['component_description'] ?? '');
        }

        $effectiveQty = $qty * (1 + $scrap / 100.0);
        $lineCostBatch = ($qty > 0 && $cost > 0) ? $effectiveQty * $cost : 0.0;

        return [
            'line_source' => $lineSource,
            'component_code' => $code,
            'component_description' => $description,
            'component_category' => $category,
            'group_name' => $groupName,
            'manual_component_type' => $lineSource === 'manual' ? ($row['manual_component_type'] ?? null) : null,
            'quantity' => $qty,
            'scrap_percent' => $scrap,
            'unit_cost' => $cost,
            'effective_qty' => round($effectiveQty, 6),
            'line_cost' => round($lineCostBatch, 6),
            'line_cost_batch' => round($lineCostBatch, 6),
        ];
    }

    private static function resolveManualComponentGroupName(string $type): string
    {
        return match ($type) {
            'MP' => 'Matéria Prima',
            'EMB' => 'Embalagens',
            default => 'Outros',
        };
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
        $rowLaborCostPerMin = (float)($row['labor_cost_per_min'] ?? 0);
        $rowMachineCostPerMin = (float)($row['machine_cost_per_min'] ?? 0);
        $rowEnergyCostPerMin = (float)($row['energy_cost_per_min'] ?? 0);
        $laborLines = $row['labor_lines'] ?? [];
        $resourceLines = $row['resource_lines'] ?? [];

        $manualLaborPerMin = InvItemOperationsRepository::sumLaborCostPerMinute($laborLines);
        if ($manualLaborPerMin <= 0 && $rowLaborCostPerMin > 0) {
            $manualLaborPerMin = $rowLaborCostPerMin;
        }
        $manualLaborQty = self::sumLaborQty($laborLines);
        $laborQtyForHours = $manualLaborQty > 0 ? $manualLaborQty : $operatorsQty;

        $resourceSplit = self::splitResourceCostsPerMinute($resourceLines, $row);
        $sapLaborPerMin = (float)($resourceSplit['sap_labor'] ?? 0);
        $equipmentPerMin = (float)($resourceSplit['equipment'] ?? 0);
        $machinePerMin = (float)($resourceSplit['machine'] ?? 0);
        $energyPerMin = (float)($resourceSplit['energy'] ?? 0);
        if ($equipmentPerMin <= 0 && ($rowMachineCostPerMin > 0 || $rowEnergyCostPerMin > 0)) {
            $equipmentPerMin = $rowMachineCostPerMin + $rowEnergyCostPerMin;
        }
        $resourcesPerMin = $sapLaborPerMin + $equipmentPerMin;
        $hasMachineDriver = ($equipmentPerMin + $machinePerMin) > 0 || $resourceLines !== [];

        if (!in_array($timeUnit, ['MIN', 'H'], true)) {
            $timeUnit = 'MIN';
        }
        $timeMinutes = $timeUnit === 'H' ? $rawTime * 60.0 : $rawTime;
        $timeHours = $timeMinutes / 60.0;

        $lineCost = 0.0;
        $manualLaborLineCost = 0.0;
        $sapLaborLineCost = 0.0;
        $equipmentLineCost = 0.0;
        $laborHours = 0.0;
        $machineHours = 0.0;
        $costSource = 'none';
        if ($timeMinutes > 0) {
            $costPerMinuteFromRoute = $manualLaborPerMin + $resourcesPerMin;
            if ($costPerMinuteFromRoute > 0) {
                $manualLaborLineCost = $timeMinutes * $manualLaborPerMin;
                $sapLaborLineCost = $timeMinutes * $sapLaborPerMin;
                $equipmentLineCost = $timeMinutes * $equipmentPerMin;
                $lineCost = $manualLaborLineCost + $sapLaborLineCost + $equipmentLineCost;
                $costSource = 'route';
            } elseif ($costHour > 0) {
                $lineCost = $timeHours * $costHour;
                $manualLaborLineCost = $lineCost;
                $costSource = 'operation_default';
            }
            $laborHours = $timeHours * $laborQtyForHours;
            if ($hasMachineDriver) {
                $machineHours = $timeHours;
            }
        }

        if (array_key_exists('override_sap_labor_line_cost_batch', $row)) {
            $sapLaborLineCost = max(0.0, (float)$row['override_sap_labor_line_cost_batch']);
        }
        if (array_key_exists('override_equipment_line_cost_batch', $row)) {
            $equipmentLineCost = max(0.0, (float)$row['override_equipment_line_cost_batch']);
        }
        if (array_key_exists('override_manual_labor_line_cost_batch', $row)) {
            $manualLaborLineCost = max(0.0, (float)$row['override_manual_labor_line_cost_batch']);
        }
        if (
            array_key_exists('override_sap_labor_line_cost_batch', $row)
            || array_key_exists('override_equipment_line_cost_batch', $row)
            || array_key_exists('override_manual_labor_line_cost_batch', $row)
        ) {
            $lineCost = $manualLaborLineCost + $sapLaborLineCost + $equipmentLineCost;
            $costSource = 'simulation_override';
        }

        return [
            'operation_code' => (string)($row['operation_code'] ?? ''),
            'operation_name' => (string)($row['operation_name'] ?? ''),
            'notes' => (string)($row['notes'] ?? ''),
            'time_minutes' => round($timeMinutes, 6),
            'labor_hours' => round($laborHours, 6),
            'machine_hours' => round($machineHours, 6),
            'labor_qty' => $laborQtyForHours,
            'operators_qty' => $operatorsQty,
            'manual_labor_cost_per_min' => round($manualLaborPerMin, 6),
            'sap_labor_cost_per_min' => round($sapLaborPerMin, 6),
            'equipment_cost_per_min' => round($equipmentPerMin, 6),
            'labor_cost_per_min' => round($manualLaborPerMin, 6),
            'machine_cost_per_min' => round($machinePerMin, 6),
            'energy_cost_per_min' => round($energyPerMin, 6),
            'resources_cost_per_min' => round($resourcesPerMin, 6),
            'default_cost_per_hour' => $costHour,
            'manual_labor_line_cost' => round($manualLaborLineCost, 6),
            'sap_labor_line_cost' => round($sapLaborLineCost, 6),
            'equipment_line_cost' => round($equipmentLineCost, 6),
            'labor_line_cost' => round($manualLaborLineCost, 6),
            'resources_line_cost' => round($sapLaborLineCost + $equipmentLineCost, 6),
            'cost_source' => $costSource,
            'line_cost' => round($lineCost, 6),
        ];
    }

    /**
     * Separa custos/min dos recursos: MO SAP (tipo LABOR) vs equipamentos (máq + energia).
     *
     * @param list<array<string, mixed>> $resourceLines
     * @param array<string, mixed> $opRow
     * @return array{sap_labor: float, equipment: float, machine: float, energy: float}
     */
    private static function splitResourceCostsPerMinute(array $resourceLines, array $opRow): array
    {
        $sapLabor = 0.0;
        $equipment = 0.0;
        $machine = 0.0;
        $energy = 0.0;

        foreach ($resourceLines as $line) {
            $qty = max(0, (int)($line['qty'] ?? 0));
            if ($qty <= 0) {
                continue;
            }
            $lineMachine = $qty * max(0, (float)($line['machine_cost_per_min'] ?? 0));
            $lineEnergy = $qty * max(0, (float)($line['energy_cost_per_min'] ?? 0));
            $lineTotal = $lineMachine + $lineEnergy;
            $machine += $lineMachine;
            $energy += $lineEnergy;
            $type = strtoupper((string)($line['resource_type'] ?? 'MACHINE'));
            if ($type === 'LABOR') {
                $sapLabor += $lineTotal;
            } else {
                $equipment += $lineTotal;
            }
        }

        if ($resourceLines === []) {
            $notes = mb_strtoupper((string)($opRow['notes'] ?? ''), 'UTF-8');
            $headerMachine = max(0, (float)($opRow['machine_cost_per_min'] ?? 0));
            $headerEnergy = max(0, (float)($opRow['energy_cost_per_min'] ?? 0));
            $headerLabor = max(0, (float)($opRow['labor_cost_per_min'] ?? 0));
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

    /**
     * @param array<string, float> $groupsMap
     * @return list<array{group_name: string, line_cost: float}>
     */
    private static function buildMaterialGroupsList(array $groupsMap): array
    {
        if ($groupsMap === []) {
            return [];
        }

        $groups = [];
        foreach ($groupsMap as $name => $cost) {
            $groups[] = [
                'group_name' => (string)$name,
                'line_cost' => round((float)$cost, 6),
            ];
        }

        usort($groups, static function (array $a, array $b): int {
            $orderA = self::materialGroupSortOrder((string)($a['group_name'] ?? ''));
            $orderB = self::materialGroupSortOrder((string)($b['group_name'] ?? ''));
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return strcasecmp((string)($a['group_name'] ?? ''), (string)($b['group_name'] ?? ''));
        });

        return $groups;
    }

    private static function resolveMaterialGroupName(string $categoryName): string
    {
        $name = trim($categoryName);
        if ($name === '') {
            return 'Outros';
        }

        $upper = mb_strtoupper($name, 'UTF-8');
        if (str_contains($upper, 'MATER') || str_contains($upper, 'MATÉRIA')) {
            return 'Matéria Prima';
        }
        if (str_contains($upper, 'EMBAL')) {
            return 'Embalagens';
        }
        if (str_contains($upper, 'INTERMED')) {
            return 'Produto Intermediário';
        }
        if (str_contains($upper, 'ACABADO') || str_contains($upper, 'PROD')) {
            return 'Produto Acabado';
        }

        return $name;
    }

    private static function materialGroupSortOrder(string $groupName): int
    {
        return match ($groupName) {
            'Matéria Prima' => 1,
            'Embalagens' => 2,
            'Produto Intermediário' => 3,
            'Produto Acabado' => 4,
            'Outros' => 99,
            default => 50,
        };
    }

    /**
     * @param list<array<string, mixed>> $laborLines
     */
    private static function sumLaborQty(array $laborLines): int
    {
        $sum = 0;
        foreach ($laborLines as $line) {
            $sum += max(0, (int)($line['qty'] ?? 0));
        }

        return $sum;
    }

    /**
     * @param array<string, mixed> $scenario
     * @return array{material_adjust_pct: float, operations_adjust_pct: float, global_adjust_pct: float}
     */
    private static function normalizeScenario(array $scenario): array
    {
        $normalized = [
            'material_adjust_pct' => (float)($scenario['material_adjust_pct'] ?? 0),
            'operations_adjust_pct' => (float)($scenario['operations_adjust_pct'] ?? 0),
            'global_adjust_pct' => (float)($scenario['global_adjust_pct'] ?? 0),
            'standard_batch_size' => isset($scenario['standard_batch_size']) && (float)$scenario['standard_batch_size'] > 0
                ? self::normalizeBatchSize((float)$scenario['standard_batch_size'])
                : null,
        ];
        if (array_key_exists('custom_bom_rows', $scenario) && is_array($scenario['custom_bom_rows'])) {
            $normalized['custom_bom_rows'] = $scenario['custom_bom_rows'];
        }
        if (array_key_exists('custom_operation_rows', $scenario) && is_array($scenario['custom_operation_rows'])) {
            $normalized['custom_operation_rows'] = $scenario['custom_operation_rows'];
        }

        return $normalized;
    }

    public static function normalizeBatchSize(float $batchSize): float
    {
        return $batchSize > 0 ? $batchSize : 1.0;
    }

    /**
     * @param list<array<string, mixed>> $lines
     * @return list<array<string, mixed>>
     */
    private static function applyBatchSizeToMaterialLines(array $lines, float $batchSize): array
    {
        return array_map(static function (array $line) use ($batchSize): array {
            $batchLineCost = (float)($line['line_cost_batch'] ?? $line['line_cost'] ?? 0);
            if (!isset($line['line_cost_batch'])) {
                $batchLineCost = (float)($line['line_cost'] ?? 0);
            }
            $line['line_cost_batch'] = round($batchLineCost, 6);
            $line['line_cost'] = round($batchLineCost / $batchSize, 6);

            return $line;
        }, $lines);
    }

    /**
     * @param list<array<string, mixed>> $lines
     * @return list<array<string, mixed>>
     */
    private static function applyBatchSizeToOperationLines(array $lines, float $batchSize): array
    {
        $costKeys = [
            'line_cost',
            'manual_labor_line_cost',
            'sap_labor_line_cost',
            'equipment_line_cost',
            'labor_line_cost',
            'resources_line_cost',
        ];

        return array_map(static function (array $line) use ($batchSize, $costKeys): array {
            foreach ($costKeys as $key) {
                if (!array_key_exists($key, $line)) {
                    continue;
                }
                $batchValue = (float)($line[$key . '_batch'] ?? $line[$key] ?? 0);
                if (!isset($line[$key . '_batch'])) {
                    $batchValue = (float)($line[$key] ?? 0);
                }
                $line[$key . '_batch'] = round($batchValue, 6);
                $line[$key] = round($batchValue / $batchSize, 6);
            }

            return $line;
        }, $lines);
    }

    /**
     * @param list<array{group_name: string, line_cost: float}> $groups
     * @return list<array{group_name: string, line_cost: float, line_cost_batch?: float}>
     */
    private static function applyBatchSizeToMaterialGroups(array $groups, float $batchSize): array
    {
        return array_map(static function (array $group) use ($batchSize): array {
            $batchCost = (float)($group['line_cost_batch'] ?? $group['line_cost'] ?? 0);
            if (!isset($group['line_cost_batch'])) {
                $batchCost = (float)($group['line_cost'] ?? 0);
            }
            $group['line_cost_batch'] = round($batchCost, 6);
            $group['line_cost'] = round($batchCost / $batchSize, 6);

            return $group;
        }, $groups);
    }
}

