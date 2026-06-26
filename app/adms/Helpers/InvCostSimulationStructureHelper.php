<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\inventory\InvItemBomRepository;
use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvOperationsRepository;

/**
 * Monta BOM e rota para simulação what-if (não grava no cadastro do item).
 */
final class InvCostSimulationStructureHelper
{
    public function hasStructureInRequest(array $request): bool
    {
        return isset($request['sim_structure_enabled'])
            || isset($request['bom_line_source'])
            || isset($request['sim_op_operation_id']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resolveBomLinesForEdit(int $itemId, array $request, bool $isProjectItem, bool $simulationMode = false): array
    {
        if ($this->hasStructureInRequest($request)) {
            if (isset($request['bom_line_source'])) {
                return $this->parseBomLinesForEdit($request, $isProjectItem, $simulationMode);
            }

            return (new InvItemBomRepository())->getByItem($itemId);
        }

        return (new InvItemBomRepository())->getByItem($itemId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resolveOperationLinesForEdit(int $itemId, array $request, float $batchSize = 1.0): array
    {
        if ($this->hasStructureInRequest($request)) {
            if (isset($request['sim_op_operation_id'])) {
                return $this->parseOperationLinesForEdit($request, $itemId, $batchSize);
            }

            return (new InvItemOperationsRepository())->getByItem($itemId);
        }

        return (new InvItemOperationsRepository())->getByItem($itemId);
    }

    /**
     * Linhas no formato esperado por InventoryCostService::computeMaterialLine.
     *
     * @param list<array<string, mixed>> $editLines
     * @return list<array<string, mixed>>
     */
    public function bomEditLinesToComputeRows(array $editLines): array
    {
        $rows = [];
        foreach ($editLines as $line) {
            $source = (string)($line['line_source'] ?? 'catalog');
            $qty = (float)($line['quantity_per_batch'] ?? 0);
            $scrap = (float)($line['scrap_percent'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            if ($source === 'manual') {
                $unitCost = (float)($line['manual_unit_cost'] ?? $line['component_cost'] ?? 0);
                if ($unitCost <= 0) {
                    continue;
                }
                $rows[] = [
                    'line_source' => 'manual',
                    'quantity_per_batch' => $qty,
                    'scrap_percent' => $scrap,
                    'manual_description' => (string)($line['manual_description'] ?? $line['component_description'] ?? ''),
                    'manual_component_type' => (string)($line['manual_component_type'] ?? 'MP'),
                    'manual_unit' => (string)($line['manual_unit'] ?? 'UN'),
                    'manual_unit_cost' => $unitCost,
                ];
                continue;
            }

            $componentId = (int)($line['component_item_id'] ?? 0);
            $cost = (float)($line['component_cost'] ?? 0);
            $code = (string)($line['component_code'] ?? '');
            $description = (string)($line['component_description'] ?? '');
            $category = (string)($line['component_category'] ?? '');

            if ($componentId > 0 && ($cost <= 0 || $code === '')) {
                $item = (new InvItemsRepository())->getOne($componentId);
                if (is_array($item)) {
                    $cost = (float)($item['average_cost'] ?? 0);
                    $code = (string)($item['code'] ?? '');
                    $description = (string)($item['description'] ?? '');
                    $category = (string)($item['category_name'] ?? '');
                }
            }

            if ($componentId <= 0 || $cost <= 0) {
                continue;
            }

            $rows[] = [
                'line_source' => 'catalog',
                'quantity_per_batch' => $qty,
                'scrap_percent' => $scrap,
                'component_code' => $code,
                'component_description' => $description,
                'component_cost' => $cost,
                'component_category' => $category,
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $editLines
     * @return list<array<string, mixed>>
     */
    public function operationEditLinesToComputeRows(array $editLines): array
    {
        $rows = [];
        foreach ($editLines as $line) {
            $operationId = (int)($line['inv_operation_id'] ?? 0);
            $time = (float)($line['time_per_batch_hours'] ?? 0);
            if ($operationId <= 0 || $time <= 0) {
                continue;
            }

            $unit = strtoupper((string)($line['time_unit'] ?? 'MIN'));
            if (!in_array($unit, ['MIN', 'H'], true)) {
                $unit = 'MIN';
            }

            $rows[] = [
                'time_per_batch_hours' => $time,
                'time_unit' => $unit,
                'operators_qty' => max(1, (int)($line['operators_qty'] ?? 1)),
                'labor_cost_per_min' => (float)($line['labor_cost_per_min'] ?? 0),
                'machine_cost_per_min' => (float)($line['machine_cost_per_min'] ?? 0),
                'energy_cost_per_min' => (float)($line['energy_cost_per_min'] ?? 0),
                'notes' => $line['notes'] ?? null,
                'operation_code' => (string)($line['operation_code'] ?? ''),
                'operation_name' => (string)($line['operation_name'] ?? ''),
                'default_cost_per_hour' => (float)($line['operation_cost_per_hour'] ?? $line['default_cost_per_hour'] ?? 0),
                'labor_lines' => is_array($line['labor_lines'] ?? null) ? $line['labor_lines'] : [],
                'resource_lines' => is_array($line['resource_lines'] ?? null) ? $line['resource_lines'] : [],
                'override_sap_labor_line_cost_batch' => $line['override_sap_labor_line_cost_batch'] ?? null,
                'override_equipment_line_cost_batch' => $line['override_equipment_line_cost_batch'] ?? null,
                'override_manual_labor_line_cost_batch' => $line['override_manual_labor_line_cost_batch'] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseBomLinesForEdit(array $request, bool $isProjectItem, bool $simulationMode = false): array
    {
        $sources = $this->normalizeArray($request['bom_line_source'] ?? null);
        $components = $this->normalizeArray($request['bom_component_item_id'] ?? null);
        $quantities = $this->normalizeArray($request['bom_quantity_per_batch'] ?? null);
        $scraps = $this->normalizeArray($request['bom_scrap_percent'] ?? null);
        $manualDescriptions = $this->normalizeArray($request['bom_manual_description'] ?? null);
        $manualTypes = $this->normalizeArray($request['bom_manual_component_type'] ?? null);
        $manualUnits = $this->normalizeArray($request['bom_manual_unit'] ?? null);
        $manualCosts = $this->normalizeArray($request['bom_manual_unit_cost'] ?? null);
        $catalogCosts = $this->normalizeArray($request['bom_catalog_unit_cost'] ?? null);

        $rowCount = max(
            count($sources),
            count($components),
            count($quantities),
            count($manualDescriptions),
            count($manualTypes),
            count($manualUnits),
            count($manualCosts),
            count($catalogCosts),
            count($scraps)
        );

        if ($rowCount === 0) {
            return [];
        }

        $itemsRepo = new InvItemsRepository();
        $lines = [];

        for ($idx = 0; $idx < $rowCount; $idx++) {
            $sourceRaw = $sources[$idx] ?? null;
            $componentId = isset($components[$idx]) ? (int)$components[$idx] : 0;
            $description = trim((string)($manualDescriptions[$idx] ?? ''));
            $unitCost = $this->parseDecimal($manualCosts[$idx] ?? 0);
            $qty = isset($quantities[$idx]) ? (float)$quantities[$idx] : 0.0;
            $scrap = isset($scraps[$idx]) ? (float)$scraps[$idx] : 0.0;

            $source = (string)$sourceRaw === 'manual' ? 'manual' : ((string)$sourceRaw === 'catalog' ? 'catalog' : null);
            if ($source === null) {
                $source = ($description !== '' || ($componentId <= 0 && $unitCost > 0)) ? 'manual' : 'catalog';
            }

            if ($this->isBomRowEmpty($source, $componentId, $description, $qty, $unitCost)) {
                continue;
            }
            if ($qty <= 0) {
                continue;
            }

            if ($source === 'manual') {
                if (!$isProjectItem && !$simulationMode) {
                    continue;
                }
                if ($description === '' || $unitCost <= 0) {
                    continue;
                }
                $type = strtoupper(trim((string)($manualTypes[$idx] ?? 'MP')));
                if (!in_array($type, ['MP', 'EMB', 'OTHER'], true)) {
                    $type = 'MP';
                }
                $unit = strtoupper(trim((string)($manualUnits[$idx] ?? 'UN')));
                $lines[] = [
                    'line_source' => 'manual',
                    'component_item_id' => null,
                    'quantity_per_batch' => $qty,
                    'scrap_percent' => $scrap,
                    'manual_description' => $description,
                    'manual_component_type' => $type,
                    'manual_unit' => $unit !== '' ? $unit : 'UN',
                    'manual_unit_cost' => $unitCost,
                    'component_code' => 'MANUAL',
                    'component_description' => $description,
                    'unit_name' => $unit !== '' ? $unit : 'UN',
                    'component_cost' => $unitCost,
                ];
                continue;
            }

            if ($componentId <= 0) {
                continue;
            }
            $item = $itemsRepo->getOne($componentId);
            if (!is_array($item)) {
                continue;
            }
            $catalogCost = isset($catalogCosts[$idx]) ? $this->parseDecimal($catalogCosts[$idx]) : 0.0;
            $resolvedCost = $catalogCost > 0
                ? $catalogCost
                : (float)($item['average_cost'] ?? 0);
            $lines[] = [
                'line_source' => 'catalog',
                'component_item_id' => $componentId,
                'quantity_per_batch' => $qty,
                'scrap_percent' => $scrap,
                'component_code' => (string)($item['code'] ?? ''),
                'component_description' => (string)($item['description'] ?? ''),
                'component_category' => (string)($item['category_name'] ?? ''),
                'unit_name' => (string)($item['unit_name'] ?? ''),
                'component_cost' => $resolvedCost,
            ];
        }

        return $lines;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseOperationLinesForEdit(array $request, int $itemId, float $batchSize = 1.0): array
    {
        $operations = $this->normalizeArray($request['sim_op_operation_id'] ?? null);
        $sequences = $this->normalizeArray($request['sim_op_sequence'] ?? null);
        $times = $this->normalizeArray($request['sim_op_time_per_batch_hours'] ?? null);
        $units = $this->normalizeArray($request['sim_op_time_unit'] ?? null);
        $rowIds = $this->normalizeArray($request['sim_op_row_id'] ?? null);
        $overrideSapSku = $this->normalizeArray($request['sim_op_override_sap_sku'] ?? null);
        $overrideEquipSku = $this->normalizeArray($request['sim_op_override_equip_sku'] ?? null);
        $overrideManualSku = $this->normalizeArray($request['sim_op_override_manual_sku'] ?? null);

        $batchSize = $batchSize > 0 ? $batchSize : 1.0;

        $existingById = [];
        foreach ((new InvItemOperationsRepository())->getByItem($itemId) as $existing) {
            $existingById[(int)($existing['id'] ?? 0)] = $existing;
        }

        $opsMaster = new InvOperationsRepository();
        $lines = [];

        foreach ($operations as $idx => $operationId) {
            $operationId = (int)$operationId;
            if ($operationId <= 0) {
                continue;
            }
            $time = $this->parseDecimal($times[$idx] ?? 0);
            if ($time <= 0) {
                continue;
            }
            $seq = isset($sequences[$idx]) ? (int)$sequences[$idx] : ($idx + 1);
            $unit = strtoupper(trim((string)($units[$idx] ?? 'MIN')));
            if (!in_array($unit, ['MIN', 'H'], true)) {
                $unit = 'MIN';
            }
            $rowId = isset($rowIds[$idx]) ? (int)$rowIds[$idx] : 0;

            if ($rowId > 0 && isset($existingById[$rowId])) {
                $row = $existingById[$rowId];
                $row['time_per_batch_hours'] = $time;
                $row['time_unit'] = $unit;
                $row['sequence'] = $seq;
                $this->applyOperationCostOverrides($row, $idx, $overrideSapSku, $overrideEquipSku, $overrideManualSku, $batchSize);
                $lines[] = $row;
                continue;
            }

            $master = $opsMaster->getOne($operationId);
            if (!is_array($master)) {
                continue;
            }

            $row = [
                'id' => 0,
                'inv_operation_id' => $operationId,
                'sequence' => $seq,
                'time_per_batch_hours' => $time,
                'time_unit' => $unit,
                'operators_qty' => 1,
                'labor_cost_per_min' => 0,
                'machine_cost_per_min' => 0,
                'energy_cost_per_min' => 0,
                'notes' => null,
                'operation_code' => (string)($master['code'] ?? ''),
                'operation_name' => (string)($master['name'] ?? ''),
                'operation_cost_per_hour' => (float)($master['default_cost_per_hour'] ?? 0),
                'labor_lines' => [],
                'resource_lines' => [],
            ];
            $this->applyOperationCostOverrides($row, $idx, $overrideSapSku, $overrideEquipSku, $overrideManualSku, $batchSize);
            $lines[] = $row;
        }

        usort($lines, static fn(array $a, array $b): int => ((int)($a['sequence'] ?? 0)) <=> ((int)($b['sequence'] ?? 0)));

        return $lines;
    }

    /**
     * @return list<mixed>
     */
    private function normalizeArray(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    private function parseDecimal(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $s = trim((string) $value);
        if ($s === '') {
            return 0.0;
        }
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $s)) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',') && !str_contains($s, '.')) {
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : 0.0;
    }

    private function isBomRowEmpty(
        string $source,
        int $componentId,
        string $description,
        float $qty,
        float $unitCost
    ): bool {
        if ($qty > 0) {
            return false;
        }
        if ($source === 'manual') {
            return $description === '' && $unitCost <= 0;
        }

        return $componentId <= 0;
    }

    /**
     * @param list<mixed> $overrideSapSku
     * @param list<mixed> $overrideEquipSku
     * @param list<mixed> $overrideManualSku
     */
    private function applyOperationCostOverrides(
        array &$row,
        int $idx,
        array $overrideSapSku,
        array $overrideEquipSku,
        array $overrideManualSku,
        float $batchSize
    ): void {
        $sapSku = (isset($overrideSapSku[$idx]) && trim((string)$overrideSapSku[$idx]) !== '')
            ? $this->parseDecimal($overrideSapSku[$idx]) : -1.0;
        $equipSku = (isset($overrideEquipSku[$idx]) && trim((string)$overrideEquipSku[$idx]) !== '')
            ? $this->parseDecimal($overrideEquipSku[$idx]) : -1.0;
        $manualSku = (isset($overrideManualSku[$idx]) && trim((string)$overrideManualSku[$idx]) !== '')
            ? $this->parseDecimal($overrideManualSku[$idx]) : -1.0;

        if ($sapSku >= 0) {
            $row['override_sap_labor_line_cost_batch'] = round($sapSku * $batchSize, 6);
        }
        if ($equipSku >= 0) {
            $row['override_equipment_line_cost_batch'] = round($equipSku * $batchSize, 6);
        }
        if ($manualSku >= 0) {
            $row['override_manual_labor_line_cost_batch'] = round($manualSku * $batchSize, 6);
        }
    }
}
