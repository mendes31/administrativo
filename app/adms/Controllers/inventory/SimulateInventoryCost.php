<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\InvCostProjectHelper;
use App\adms\Helpers\InvCostSimulationStructureHelper;
use App\adms\Models\Repository\inventory\InvCostPeriodItemsRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Models\Repository\inventory\InvCostProductionWarehousesRepository;
use App\adms\Models\Repository\inventory\InvCostSimulationsRepository;
use App\adms\Models\Repository\inventory\InvItemBomRepository;
use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvOperationsRepository;
use App\adms\Models\Repository\inventory\InvUnitsRepository;
use App\adms\Models\Services\InvCostFixedAllocationEngine;
use App\adms\Models\Services\InvCostPeriodDriversService;
use App\adms\Models\Services\InvCostProductionAggregationService;
use App\adms\Models\Services\InvCostProductionEfficiencyService;
use App\adms\Models\Services\InventoryCostService;
use App\adms\Views\Services\LoadViewService;

class SimulateInventoryCost
{
    private array|string|null $data = null;

    public function index(int|string $id = 0): void
    {
        $itemId = (int)$id;
        if ($itemId <= 0 && isset($_GET['inv_item_id'])) {
            $legacyId = (int)$_GET['inv_item_id'];
            if ($legacyId > 0) {
                header('Location: ' . $_ENV['URL_ADM'] . 'simulate-inventory-cost/' . $legacyId);
                return;
            }
        }

        if ($itemId <= 0) {
            $_SESSION['error'] = 'Informe o item para simular o custo.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            return;
        }

        $itemsRepo = new InvItemsRepository();
        $item = $itemsRepo->getOne($itemId);
        if (!$item) {
            $_SESSION['error'] = 'Item não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            return;
        }

        $batchSize = $this->parseBatchSize($this->input('standard_batch_size', '1'));
        $requestData = $this->requestData();
        $structureHelper = new InvCostSimulationStructureHelper();
        $isProjectItem = InvCostProjectHelper::isProjectItem($item);

        $editBom = $structureHelper->resolveBomLinesForEdit($itemId, $requestData, $isProjectItem, true);
        $editOperations = $structureHelper->resolveOperationLinesForEdit(
            itemId: $itemId,
            request: $requestData,
            batchSize: $batchSize
        );

        $scenario = [
            'material_adjust_pct' => $this->parsePct($this->input('material_adjust_pct', '0')),
            'operations_adjust_pct' => $this->parsePct($this->input('operations_adjust_pct', '0')),
            'global_adjust_pct' => $this->parsePct($this->input('global_adjust_pct', '0')),
            'standard_batch_size' => $batchSize,
        ];
        if (isset($requestData['bom_line_source'])) {
            $scenario['custom_bom_rows'] = $structureHelper->bomEditLinesToComputeRows($editBom);
        }
        if (isset($requestData['sim_op_operation_id'])) {
            $scenario['custom_operation_rows'] = $structureHelper->operationEditLinesToComputeRows($editOperations);
        }

        $periodId = (int)($this->input('inv_cost_period_id', '0'));
        $warehouseScope = (string)($this->input('warehouse_scope', 'all'));
        $warehouseCodes = $this->parseWarehouseCodes($warehouseScope, $requestData['warehouse_codes'] ?? null);

        $productionEfficiency = null;
        if ($periodId > 0) {
            $period = (new InvCostPeriodsRepository())->getOne($periodId);
            if (is_array($period)) {
                $erpCode = trim((string)($item['erp_code'] ?? ''));
                $productionEfficiency = (new InvCostProductionEfficiencyService())->aggregateForItemInPeriod(
                    $itemId,
                    $erpCode !== '' ? $erpCode : null,
                    (string)$period['date_from'],
                    (string)$period['date_to'],
                    $warehouseCodes,
                    $periodId
                );
                if ($productionEfficiency !== null) {
                    $scenario['production_efficiency_ratio'] = $productionEfficiency['efficiency_ratio'];
                    (new InvCostPeriodItemsRepository())->upsertCalculatedEfficiency(
                        $periodId,
                        $itemId,
                        (float)$productionEfficiency['efficiency_ratio'],
                        (float)($productionEfficiency['min_batch_size'] ?? 0) ?: null
                    );
                }
                $tariff = (float)($period['kwh_tariff'] ?? 0);
                if ($tariff > 0) {
                    $scenario['kwh_tariff'] = $tariff;
                }
            }
        }

        $this->data['selected_item_id'] = $itemId;
        $this->data['selected_item'] = $item;
        $this->data['is_project_item'] = $isProjectItem;
        $this->data['scenario'] = $scenario;
        $this->data['scenario_batch_size'] = $batchSize;
        $this->data['edit_bom'] = $editBom;
        $this->data['edit_operations'] = $editOperations;
        $this->data['baseline_bom'] = (new InvItemBomRepository())->getByItem($itemId);
        $this->data['baseline_operations'] = (new InvItemOperationsRepository())->getByItemForCosting($itemId);
        $this->data['structure_customized'] = $structureHelper->hasStructureInRequest($requestData);
        $this->data['listBomItems'] = (new InvItemsRepository())->getAllForSelectWithAdminType();
        $this->data['listUnits'] = (new InvUnitsRepository())->getAllForSelect();
        $this->data['listOperations'] = (new InvOperationsRepository())->getAllForSelect();
        $this->data['breakdown'] = InventoryCostService::calculateBreakdown($itemId, $scenario);
        $this->data['production_efficiency'] = $productionEfficiency;
        $this->data['saved_simulations'] = (new InvCostSimulationsRepository())->getByItem($itemId, 15);

        $this->data['cost_periods'] = (new InvCostPeriodsRepository())->getForSelect();
        $this->data['production_warehouses'] = (new InvCostProductionWarehousesRepository())->getAllActive();
        $this->data['selected_period_id'] = $periodId;
        $this->data['warehouse_scope'] = $warehouseScope === 'selected' ? 'selected' : 'all';
        $this->data['selected_warehouse_codes'] = $warehouseCodes ?? [];

        $productionAggregation = null;
        $currentItemProduction = null;
        $periodDrivers = null;
        $currentItemPeriodDrivers = null;
        $currentItemCriterionDrivers = null;
        $cfixAllocation = null;
        $suggestedPrice = null;
        if ($periodId > 0) {
            $aggService = new InvCostProductionAggregationService();
            $productionAggregation = $aggService->aggregateByPeriod($periodId, $warehouseCodes);
            $erpCode = trim((string)($item['erp_code'] ?? ''));
            $currentItemProduction = $aggService->findItemInAggregation(
                $productionAggregation,
                $itemId,
                $erpCode !== '' ? $erpCode : null
            );

            $driversService = new InvCostPeriodDriversService();
            $periodDrivers = $driversService->aggregateDriversByPeriod($periodId, $warehouseCodes);
            $currentItemPeriodDrivers = $driversService->findItemInDrivers(
                $periodDrivers,
                $itemId,
                $erpCode !== '' ? $erpCode : null
            );

            $criterionAggregation = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId, $warehouseCodes);
            $currentItemCriterionDrivers = (new InvCostCriterionDriversService())->findItemShares(
                $criterionAggregation,
                $itemId,
                $erpCode !== '' ? $erpCode : null
            );

            $cfixEngine = new InvCostFixedAllocationEngine();
            $cfixAllocation = $cfixEngine->allocateForItem($periodId, $itemId, $warehouseCodes);

            $periodItem = (new InvCostPeriodItemsRepository())->getOne($periodId, $itemId);
            $targetMargin = is_array($periodItem) ? (float)($periodItem['target_margin_pct'] ?? 0) : 0.0;
            $simUnit = (float)($this->data['breakdown']['simulated_total'] ?? 0);
            $cfixUnit = (float)($cfixAllocation['cfix_total'] ?? 0);
            $fullCost = $simUnit + $cfixUnit;
            if ($targetMargin > 0 && $targetMargin < 100 && $fullCost > 0) {
                $suggestedPrice = round($fullCost / (1 - ($targetMargin / 100)), 4);
            }
        }
        $this->data['production_aggregation'] = $productionAggregation;
        $this->data['current_item_production'] = $currentItemProduction;
        $this->data['period_drivers'] = $periodDrivers;
        $this->data['current_item_period_drivers'] = $currentItemPeriodDrivers;
        $this->data['current_item_criterion_drivers'] = $currentItemCriterionDrivers ?? null;
        $this->data['cfix_allocation'] = $cfixAllocation;
        $this->data['suggested_price'] = $suggestedPrice;

        $pageElements = [
            'title_head' => 'Simulação de Custos',
            'menu' => 'estoque',
            'buttonPermission' => [
                'ListInventoryItems',
                'ViewInventoryItem',
                'SaveInventoryCostSimulation',
                'ExportInventoryCostSimulationPdf',
                'ListInvCostProductionBatches',
                'ListInvCostPeriods',
            ],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/costs/simulate', $this->data);
        $loadView->loadView();
    }

    private function parsePct(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }
        if (!is_numeric($value)) {
            return 0.0;
        }

        return round((float)$value, 4);
    }

    private function parseBatchSize(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }
        if (!is_numeric($value)) {
            return 1.0;
        }
        $batch = (float)$value;

        return $batch > 0 ? round($batch, 6) : 1.0;
    }

    /**
     * @param mixed $rawCodes
     * @return list<string>|null null = todos os depósitos
     */
    private function parseWarehouseCodes(string $scope, mixed $rawCodes): ?array
    {
        if ($scope !== 'selected') {
            return null;
        }
        if (!is_array($rawCodes)) {
            $rawCodes = $rawCodes !== null && $rawCodes !== '' ? [$rawCodes] : [];
        }
        $normalized = [];
        foreach ($rawCodes as $code) {
            $value = strtoupper(trim((string)$code));
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return $normalized === [] ? null : array_values(array_unique($normalized));
    }

    /**
     * @return array<string, mixed>
     */
    private function requestData(): array
    {
        if (!empty($_POST)) {
            return $_POST;
        }

        return $_GET;
    }

    private function input(string $key, mixed $default = null): mixed
    {
        $data = $this->requestData();

        return $data[$key] ?? $default;
    }
}
