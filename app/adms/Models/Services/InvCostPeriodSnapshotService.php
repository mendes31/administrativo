<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\InvCostBatchAdoptedHelper;
use App\adms\Helpers\InvCostComplexityHelper;
use App\adms\Models\Repository\inventory\InvCostExpensePoolsRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodSkuSnapshotsRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use Throwable;

/**
 * Snapshot materializado por período/SKU — alimenta as abas SKUs e Resultados.
 * Cada período tem registros isolados; recalcular um período não altera os demais.
 */
class InvCostPeriodSnapshotService
{
    public function hasSnapshot(int $periodId): bool
    {
        return (new InvCostPeriodSkuSnapshotsRepository())->countByPeriod($periodId) > 0;
    }

    public function countForPeriod(int $periodId): int
    {
        return (new InvCostPeriodSkuSnapshotsRepository())->countByPeriod($periodId);
    }

    /**
     * Resumo CFIX a partir do snapshot (sem recalcular drivers/BOM).
     *
     * @return array<string, mixed>|null
     */
    public function getAllocationSummaryFromSnapshot(int $periodId, float $totalExpense): ?array
    {
        if ($periodId <= 0 || !$this->hasSnapshot($periodId)) {
            return null;
        }

        $poolsRepo = new InvCostExpensePoolsRepository();
        $totalAllocated = $poolsRepo->sumCfixTotalFromSnapshots($periodId);
        $poolsWithoutCriterion = $poolsRepo->sumAmountWithoutCriterion($periodId);
        $unallocatedTotal = max(0, $totalExpense - $totalAllocated);

        return [
            'period_id' => $periodId,
            'total_expense' => round($totalExpense, 4),
            'total_cfix_allocated' => round($totalAllocated, 4),
            'by_item' => [],
            'pools_without_criterion' => round($poolsWithoutCriterion, 4),
            'unallocated_without_recipient' => round(max(0, $unallocatedTotal - $poolsWithoutCriterion), 4),
            'unallocated_expense' => round($unallocatedTotal, 4),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForPeriod(int $periodId, ?string $filter = null): array
    {
        if ($periodId <= 0) {
            return [];
        }

        $rows = array_map(
            fn(array $record): array => $this->mapRecordToView($record),
            (new InvCostPeriodSkuSnapshotsRepository())->listByPeriod($periodId)
        );

        return $this->applyFilter($rows, $filter);
    }

    /**
     * Recalcula e persiste snapshot apenas do período informado.
     *
     * @return array{success: bool, message: string, row_count: int}
     */
    public function recalculate(int $periodId): array
    {
        if ($periodId <= 0) {
            return ['success' => false, 'message' => 'Período inválido.', 'row_count' => 0];
        }

        $periodRepo = new InvCostPeriodsRepository();
        $period = $periodRepo->getOne($periodId);
        if ($period === false) {
            return ['success' => false, 'message' => 'Período não encontrado.', 'row_count' => 0];
        }

        InvCostAnalysisDriverService::clearMaterialLinesCache();

        $productionAggService = new InvCostProductionAggregationService();
        $productionAggregation = $productionAggService->aggregateByPeriod($periodId);
        $criterionAggregation = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId);

        $productionRows = (new InvCostPeriodProductionItemsService())->listForPeriod(
            periodId: $periodId,
            productionAggregation: $productionAggregation,
            criterionAggregation: $criterionAggregation,
            bypassSnapshot: true
        );

        $allocation = (new InvCostFixedAllocationEngine())->allocateByPeriod($periodId);
        $byItemCfix = $allocation['by_item'] ?? [];
        $tariff = (float)($period['kwh_tariff'] ?? 0);
        $computedAt = date('Y-m-d H:i:s');

        $snapshotRows = [];
        foreach ($productionRows as $row) {
            $itemId = (int)($row['inv_item_id'] ?? 0);
            $qty = (float)($row['total_qty'] ?? 0);
            $effRatio = isset($row['efficiency_ratio']) ? (float)$row['efficiency_ratio'] : null;

            $cvarSimUnit = null;
            $cvarEnergyUnit = null;
            $cvarMpUnit = null;
            $cvarMaeUnit = null;
            $cvarPeriodTotal = null;
            $cfixTotal = 0.0;
            $cfixUnit = null;
            $cfixPeriodTotal = null;
            $fullCostUnit = null;
            $fullCostPeriodTotal = null;
            $salePriceNet = isset($row['sale_price_net']) && $row['sale_price_net'] !== null && $row['sale_price_net'] !== ''
                ? (float)$row['sale_price_net']
                : null;
            $markupPct = null;
            $costingBatchSize = $this->resolveCostingBatchSize($row);

            if ($itemId > 0) {
                $scenario = ['standard_batch_size' => $costingBatchSize];
                if ($effRatio !== null && $effRatio > 0) {
                    $scenario['production_efficiency_ratio'] = $effRatio;
                }
                if ($tariff > 0) {
                    $scenario['kwh_tariff'] = $tariff;
                }

                $breakdown = InventoryCostService::calculateBreakdown($itemId, $scenario);
                $cvarMpUnit = (float)($breakdown['simulated_cvar_mp_cost'] ?? 0);
                $cvarMaeUnit = (float)($breakdown['simulated_cvar_mae_cost'] ?? 0);
                $cvarEnergyUnit = (float)($breakdown['simulated_cvar_energy_cost'] ?? 0);
                $cvarSimUnit = round($cvarMpUnit + $cvarMaeUnit + $cvarEnergyUnit, 6);

                if ($qty > 0) {
                    $cvarPeriodTotal = round($cvarSimUnit * $qty, 4);
                }

                $cfixTotal = (float)($byItemCfix[$itemId]['cfix_total'] ?? 0);
                $cfixPeriodTotal = $cfixTotal > 0 ? round($cfixTotal, 4) : 0.0;
                $cfixUnit = ($cfixTotal > 0 && $qty > 0)
                    ? round($cfixTotal / $qty, 6)
                    : ($cfixTotal > 0 ? null : 0.0);
                $fullCostUnit = round($cvarSimUnit + (float)($cfixUnit ?? 0), 6);

                if ($qty > 0) {
                    $fullCostPeriodTotal = round($fullCostUnit * $qty, 4);
                }

                if ($salePriceNet !== null && $salePriceNet > 0 && $fullCostUnit !== null && $fullCostUnit > 0) {
                    $markupPct = round((($salePriceNet - $fullCostUnit) / $fullCostUnit) * 100, 4);
                }
            }

            $snapshotRows[] = array_merge($row, [
                'cvar_sim_unit' => $cvarSimUnit,
                'cvar_mp_unit' => $cvarMpUnit,
                'cvar_mae_unit' => $cvarMaeUnit,
                'cvar_energy_unit' => $cvarEnergyUnit,
                'costing_batch_size' => $costingBatchSize,
                'cvar_batch' => $cvarPeriodTotal,
                'cfix_total' => round($cfixTotal, 4),
                'cfix_unit' => $cfixUnit,
                'cfix_batch' => $cfixPeriodTotal,
                'full_cost_unit' => $fullCostUnit,
                'full_cost_batch' => $fullCostPeriodTotal,
                'sale_price_net' => $salePriceNet,
                'markup_pct' => $markupPct,
                'computed_at' => $computedAt,
            ]);
        }

        $inputsHash = $this->buildInputsHash($periodId, $period, count($snapshotRows));

        $snapRepo = new InvCostPeriodSkuSnapshotsRepository();
        $conn = $snapRepo->getConnection();
        $conn->beginTransaction();

        try {
            $snapRepo->deleteByPeriod($periodId);
            $inserted = $snapRepo->insertBatch($periodId, $snapshotRows);
            $periodRepo->updateSnapshotMeta($periodId, $computedAt, $inserted, $inputsHash);
            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            return [
                'success' => false,
                'message' => 'Erro ao gravar snapshot: ' . $e->getMessage(),
                'row_count' => 0,
            ];
        }

        return [
            'success' => true,
            'message' => sprintf('Snapshot atualizado: %d SKU(s) em %s.', $inserted, date('d/m/Y H:i', strtotime($computedAt))),
            'row_count' => $inserted,
        ];
    }

    /**
     * Recalcula silenciosamente (logs em falha); usado pelos gatilhos pós-salvar.
     */
    public static function tryRecalculate(int $periodId): bool
    {
        if ($periodId <= 0) {
            return false;
        }

        try {
            $result = (new self())->recalculate($periodId);

            return !empty($result['success']);
        } catch (Throwable $e) {
            error_log('[InvCostPeriodSnapshotService] recalculate failed period=' . $periodId . ': ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @param array<string, mixed> $period
     */
    private function buildInputsHash(int $periodId, array $period, int $rowCount): string
    {
        $payload = [
            'period_id' => $periodId,
            'date_from' => (string)($period['date_from'] ?? ''),
            'date_to' => (string)($period['date_to'] ?? ''),
            'kwh_tariff' => round((float)($period['kwh_tariff'] ?? 0), 6),
            'energy_kwh_hvac' => round((float)($period['energy_kwh_hvac'] ?? 0), 6),
            'energy_kwh_production_common' => round((float)($period['energy_kwh_production_common'] ?? 0), 6),
            'energy_kwh_direct_cfix' => round((float)($period['energy_kwh_direct_cfix'] ?? 0), 6),
            'row_count' => $rowCount,
            'updated_at' => (string)($period['updated_at'] ?? ''),
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function mapRecordToView(array $record): array
    {
        $itemId = (int)($record['inv_item_id'] ?? 0);
        $batchSize = $this->toNullableFloat($record['standard_batch_size'] ?? null);
        $qty = $this->toNullableFloat($record['total_qty'] ?? null);
        $costingBatchSize = $this->toNullableFloat($record['costing_batch_size'] ?? null)
            ?? $this->resolveCostingBatchSize($record);

        $cvarMp = $this->toNullableFloat($record['cvar_mp_unit'] ?? null);
        $cvarMae = $this->toNullableFloat($record['cvar_mae_unit'] ?? null);
        $cvarSim = $this->toNullableFloat($record['cvar_sim_unit'] ?? null);
        $cvarEnergy = $this->toNullableFloat($record['cvar_energy_unit'] ?? null);
        $cvarPeriodTotal = $this->toNullableFloat($record['cvar_batch'] ?? null);
        $cfixUnit = $this->toNullableFloat($record['cfix_unit'] ?? null);
        $cfixPeriodTotal = $this->toNullableFloat($record['cfix_batch'] ?? null);
        $fullUnit = $this->toNullableFloat($record['full_cost_unit'] ?? null);
        $fullPeriodTotal = $this->toNullableFloat($record['full_cost_batch'] ?? null);
        $salePrice = $this->toNullableFloat($record['sale_price_net'] ?? null);
        $markup = $this->toNullableFloat($record['markup_pct'] ?? null);

        if ($cvarSim === null && $cvarMp !== null) {
            $cvarSim = round($cvarMp + (float)($cvarMae ?? 0) + (float)($cvarEnergy ?? 0), 6);
        }

        if ($qty !== null && $qty > 0) {
            if ($cvarPeriodTotal === null && $cvarSim !== null) {
                $cvarPeriodTotal = round($cvarSim * $qty, 4);
            }
            if ($cfixPeriodTotal === null && $cfixUnit !== null) {
                $cfixPeriodTotal = round($cfixUnit * $qty, 4);
            }
            if ($fullPeriodTotal === null && $fullUnit !== null) {
                $fullPeriodTotal = round($fullUnit * $qty, 4);
            }
        }

        if ($markup === null && $salePrice !== null && $salePrice > 0 && $fullUnit !== null && $fullUnit > 0) {
            $markup = round((($salePrice - $fullUnit) / $fullUnit) * 100, 4);
        }

        $adoptedBatch = $this->toNullableFloat($record['batch_size_adopted'] ?? null);
        $avgPerRound = $this->toNullableFloat($record['qty_avg_per_round'] ?? null);
        $batchesProduced = $this->toNullableFloat($record['batches_produced'] ?? null);
        if ($adoptedBatch === null && $qty !== null && $qty > 0) {
            $ctx = InvCostBatchAdoptedHelper::resolveProductionContext(
                ['batch_size_theoretical' => InvCostBatchAdoptedHelper::theoreticalFixedFromCatalog($batchSize)],
                $qty,
                InvCostBatchAdoptedHelper::resolvePhysicalBatchesCount(
                    (int)($record['batches_count'] ?? 0)
                ),
                $batchSize
            );
            $adoptedBatch = (float)$ctx['batch_size_adopted'];
            $avgPerRound = $this->toNullableFloat($ctx['qty_avg_per_round'] ?? null);
            $batchesProduced = (float)$ctx['batches_produced'];
        }

        return [
            'inv_item_id' => $itemId > 0 ? $itemId : null,
            'erp_code' => (string)($record['erp_code'] ?? ''),
            'item_description' => (string)($record['item_description'] ?? ''),
            'category_name' => (string)($record['category_name'] ?? ''),
            'linked' => !empty($record['linked']),
            'is_scenario' => !empty($record['is_scenario']),
            'has_scenario' => !empty($record['has_scenario']),
            'is_produto_acabado' => !empty($record['is_produto_acabado']),
            'batches_count' => (int)($record['batches_count'] ?? 0),
            'total_qty' => $this->toNullableFloat($record['total_qty'] ?? null),
            'qty_planned' => $this->toNullableFloat($record['qty_planned'] ?? null),
            'efficiency_ratio' => $this->toNullableFloat($record['efficiency_ratio'] ?? null),
            'efficiency_pct' => $this->toNullableFloat($record['efficiency_pct'] ?? null),
            'standard_batch_size' => InvCostBatchAdoptedHelper::catalogBatchForDisplay($batchSize),
            'batch_size_adopted' => $adoptedBatch,
            'qty_avg_per_round' => $avgPerRound,
            'batches_produced' => $batchesProduced,
            'costing_batch_size' => $costingBatchSize,
            'energy_class' => (string)($record['energy_class'] ?? ''),
            'complexity_level' => InvCostComplexityHelper::resolveForCosting($record['complexity_level'] ?? null),
            'complexity_factor' => $this->toNullableFloat($record['complexity_factor'] ?? null),
            'production_line' => $record['production_line'] ?? null,
            'mp_lines' => (int)($record['mp_lines'] ?? 0),
            'mae_lines' => (int)($record['mae_lines'] ?? 0),
            'analysis_lines_per_batch' => (int)($record['analysis_lines_per_batch'] ?? 0),
            'analysis_count_total' => $this->toNullableFloat($record['analysis_count_total'] ?? null),
            'driver_4' => $this->toNullableFloat($record['driver_4'] ?? null),
            'driver_6' => $this->toNullableFloat($record['driver_6'] ?? null),
            'share_criterion_4' => $this->toNullableFloat($record['share_criterion_4'] ?? null),
            'share_criterion_6' => $this->toNullableFloat($record['share_criterion_6'] ?? null),
            'energy_class_from_item' => !empty($record['energy_class_from_item']),
            'suggested_energy_class' => (string)($record['suggested_energy_class'] ?? ''),
            'cvar_sim_unit' => $cvarSim,
            'cvar_mp_unit' => $cvarMp,
            'cvar_mae_unit' => $cvarMae,
            'cvar_energy_unit' => $cvarEnergy,
            'cvar_period_total' => $cvarPeriodTotal,
            'cvar_batch' => $cvarPeriodTotal,
            'cfix_total' => $this->toNullableFloat($record['cfix_total'] ?? null),
            'cfix_unit' => $cfixUnit,
            'cfix_period_total' => $cfixPeriodTotal,
            'cfix_batch' => $cfixPeriodTotal,
            'full_cost_unit' => $fullUnit,
            'full_cost_period_total' => $fullPeriodTotal,
            'full_cost_batch' => $fullPeriodTotal,
            'sale_price_net' => $salePrice,
            'markup_pct' => $markup,
            'snapshot_computed_at' => $record['computed_at'] ?? null,
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function applyFilter(array $rows, ?string $filter): array
    {
        $filter = trim((string)($filter ?? ''));
        if ($filter === '') {
            return $rows;
        }

        if (mb_strtoupper($filter, 'UTF-8') === 'PA') {
            return array_values(array_filter(
                $rows,
                static fn(array $row): bool => !empty($row['is_produto_acabado'])
            ));
        }

        $needle = mb_strtolower($filter, 'UTF-8');

        return array_values(array_filter($rows, static function (array $row) use ($needle): bool {
            $erpCode = trim((string)($row['erp_code'] ?? ''));
            if ($erpCode !== '' && str_contains(mb_strtolower($erpCode, 'UTF-8'), $needle)) {
                return true;
            }

            $description = trim((string)($row['item_description'] ?? ''));
            if ($description !== '' && str_contains(mb_strtolower($description, 'UTF-8'), $needle)) {
                return true;
            }

            $categoryName = trim((string)($row['category_name'] ?? ''));

            return $categoryName !== '' && str_contains(mb_strtolower($categoryName, 'UTF-8'), $needle);
        }));
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float)$value : null;
    }

    /**
     * Divisor do CVAR/un. na BOM (unidade comercial = 1).
     *
     * @param array<string, mixed> $row
     */
    private function resolveCostingBatchSize(array $row): float
    {
        return 1.0;
    }
}
