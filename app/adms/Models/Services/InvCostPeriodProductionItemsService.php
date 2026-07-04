<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\InvCostBatchAdoptedHelper;
use App\adms\Helpers\InvCostComplexityHelper;
use App\adms\Models\Repository\inventory\InvCostPeriodItemsRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;

class InvCostPeriodProductionItemsService
{
    /**
     * @param list<string>|null $warehouseCodes
     * @param array<string, mixed>|null $productionAggregation
     * @param array<string, mixed>|null $criterionAggregation Saída de aggregateAllCriteria (evita recomputar % 4/6)
     * @return list<array<string, mixed>>
     */
    public function listForPeriod(
        int $periodId,
        ?array $warehouseCodes = null,
        ?string $filter = null,
        ?array $productionAggregation = null,
        ?array $criterionAggregation = null,
        bool $bypassSnapshot = false
    ): array {
        if ($periodId <= 0) {
            return [];
        }

        $snapshotService = new InvCostPeriodSnapshotService();
        if (!$bypassSnapshot && $snapshotService->hasSnapshot($periodId)) {
            return $snapshotService->listForPeriod($periodId, $filter);
        }

        $aggregation = $productionAggregation
            ?? (new InvCostProductionAggregationService())->aggregateByPeriod($periodId, $warehouseCodes);

        $prodRows = $aggregation['items'] ?? [];
        if ($prodRows === []) {
            return [];
        }

        $savedMap = (new InvCostPeriodItemsRepository())->getMapByPeriod($periodId);
        $defaultsService = new InvCostPeriodItemDefaultsService();
        $analysisDriver = new InvCostAnalysisDriverService();
        $shareMaps = $this->buildCriterionShareMaps($periodId, $warehouseCodes, $criterionAggregation);

        $ids = [];
        $erpCodes = [];
        foreach ($prodRows as $prod) {
            $id = (int)($prod['inv_item_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
            $erp = trim((string)($prod['erp_code'] ?? ''));
            if ($erp !== '') {
                $erpCodes[] = $erp;
            }
        }

        $itemMaps = (new InvItemsRepository())->getDetailedMapForProduction($ids, $erpCodes);
        $prodRows = $this->prefilterProductionRows($prodRows, $filter, $itemMaps);

        $rows = [];
        foreach ($prodRows as $prod) {
            $rows[] = $this->buildRow(
                $prod,
                $itemMaps,
                $savedMap,
                $defaultsService,
                $analysisDriver,
                $shareMaps
            );
        }

        usort($rows, static function (array $a, array $b): int {
            return strcasecmp(
                (string)($a['item_description'] ?? ''),
                (string)($b['item_description'] ?? '')
            );
        });

        return $rows;
    }

    /**
     * @param array{by_id: array<int, array<string, mixed>>, by_erp: array<string, array<string, mixed>>} $itemMaps
     * @param array<int, array<string, mixed>> $savedMap
     * @param array{by_id: array<int, array{share_criterion_4: float, share_criterion_6: float}>, by_erp: array<string, array{share_criterion_4: float, share_criterion_6: float}>} $shareMaps
     * @return array<string, mixed>
     */
    private function buildRow(
        array $prod,
        array $itemMaps,
        array $savedMap,
        InvCostPeriodItemDefaultsService $defaultsService,
        InvCostAnalysisDriverService $analysisDriver,
        array $shareMaps
    ): array {
        $itemId = (int)($prod['inv_item_id'] ?? 0);
        $erpCode = trim((string)($prod['erp_code'] ?? ''));
        $erpKey = mb_strtoupper($erpCode, 'UTF-8');

        $itemMeta = null;
        if ($itemId > 0 && isset($itemMaps['by_id'][$itemId])) {
            $itemMeta = $itemMaps['by_id'][$itemId];
        } elseif ($erpKey !== '' && isset($itemMaps['by_erp'][$erpKey])) {
            $itemMeta = $itemMaps['by_erp'][$erpKey];
            $itemId = (int)($itemMeta['id'] ?? 0);
        }

        $saved = $itemId > 0 ? ($savedMap[$itemId] ?? null) : null;
        $effective = $itemId > 0
            ? $defaultsService->mergeWithDefaults($itemId, $saved, $itemMeta)
            : [
                'energy_class' => '',
                'complexity_level' => InvCostComplexityHelper::LEVEL_NA,
                'analysis_count' => 0,
                'batch_size_adopted' => null,
                'batch_size_theoretical' => null,
                'qty_avg_per_round' => null,
                'production_line' => null,
                'sale_price_net' => null,
                'target_margin_pct' => null,
                'efficiency_pct' => null,
            ];

        $standardBatchRaw = is_array($itemMeta) ? ($itemMeta['standard_batch_size'] ?? null) : null;
        $itemEnergyClass = is_array($itemMeta) ? trim((string)($itemMeta['energy_class'] ?? '')) : '';
        $categoryName = is_array($itemMeta) ? trim((string)($itemMeta['category_name'] ?? '')) : '';
        $qtyProduced = (float)($prod['qty_produced'] ?? $prod['total_qty'] ?? 0);
        $efficiencyPct = isset($prod['efficiency_pct']) ? (float)$prod['efficiency_pct'] : null;
        $efficiencyRatio = isset($prod['efficiency_ratio']) ? (float)$prod['efficiency_ratio'] : null;

        $periodItemForMetrics = $itemId > 0 ? $effective : null;
        $metrics = InvCostBatchAdoptedHelper::resolveProductionMetrics(
            $periodItemForMetrics,
            $qtyProduced,
            max(0, (int)($prod['batches_count'] ?? 0)),
            $standardBatchRaw
        );

        $adoptedBatch = (float)$metrics['batch_size_adopted'];
        $batchesProduced = (float)$metrics['batches_produced'];
        $qtyPlanned = $metrics['qty_planned'];

        if ($efficiencyPct === null && $saved !== null && isset($saved['efficiency_pct']) && $saved['efficiency_pct'] !== null && $saved['efficiency_pct'] !== '') {
            $efficiencyPct = (float)$saved['efficiency_pct'];
        }
        if ($efficiencyRatio === null) {
            $efficiencyRatio = (float)$metrics['efficiency_ratio'];
        }
        if ($efficiencyPct === null && $efficiencyRatio !== null && $efficiencyRatio > 0) {
            $efficiencyPct = round($efficiencyRatio * 100, 2);
        }

        $efficiencyForAnalysis = $analysisDriver->normalizeEfficiencyRatio(
            $efficiencyRatio,
            $qtyProduced > 0 ? $qtyProduced : null,
            $qtyPlanned
        );

        $periodItemForDriver = $itemId > 0 ? $effective : null;
        $analysisMetrics = $itemId > 0
            ? $analysisDriver->metricsForItem($itemId, $batchesProduced, $periodItemForDriver, $efficiencyForAnalysis)
            : [
                'complexity_factor' => 0.0,
                'mp_lines' => 0,
                'mae_lines' => 0,
                'analysis_lines_per_batch' => 0,
                'qty_mp_mae_per_batch' => 0.0,
                'analysis_count_total' => 0.0,
                'driver_4' => 0.0,
                'driver_6' => 0.0,
            ];

        $shares = $itemId > 0 && isset($shareMaps['by_id'][$itemId])
            ? $shareMaps['by_id'][$itemId]
            : ($erpKey !== '' && isset($shareMaps['by_erp'][$erpKey])
                ? $shareMaps['by_erp'][$erpKey]
                : ['share_criterion_4' => 0.0, 'share_criterion_6' => 0.0]);

        return array_merge($prod, $effective, $analysisMetrics, $shares, $metrics, [
            'inv_item_id' => $itemId > 0 ? $itemId : null,
            'erp_code' => $erpCode,
            'item_description' => trim((string)($prod['description'] ?? ($itemMeta['description'] ?? ''))),
            'category_name' => $categoryName,
            'is_produto_acabado' => self::isProdutoAcabado($categoryName, $erpCode),
            'efficiency_ratio' => $efficiencyRatio,
            'efficiency_pct' => $efficiencyPct,
            'qty_theoretical' => $qtyPlanned,
            'is_scenario' => !empty($prod['is_scenario']),
            'has_scenario' => !empty($prod['has_scenario']),
            'linked' => $itemId > 0,
            'has_saved_override' => $saved !== null,
            'energy_class_from_item' => $itemEnergyClass !== '',
            'suggested_energy_class' => $itemId > 0 && $itemEnergyClass === '' && is_array($itemMeta)
                ? $defaultsService->suggestEnergyClass(
                    mb_strtoupper((string)($itemMeta['description'] ?? ''), 'UTF-8'),
                    mb_strtoupper((string)($itemMeta['category_name'] ?? ''), 'UTF-8')
                )
                : '',
        ]);
    }

    /**
     * @param list<array<string, mixed>> $prodRows
     * @param array{by_id: array<int, array<string, mixed>>, by_erp: array<string, array<string, mixed>>} $itemMaps
     * @return list<array<string, mixed>>
     */
    private function prefilterProductionRows(array $prodRows, ?string $filter, array $itemMaps): array
    {
        $filter = trim((string)($filter ?? ''));
        if ($filter === '') {
            return $prodRows;
        }

        if (mb_strtoupper($filter, 'UTF-8') === 'PA') {
            return array_values(array_filter($prodRows, function (array $prod) use ($itemMaps): bool {
                $itemId = (int)($prod['inv_item_id'] ?? 0);
                $erpCode = trim((string)($prod['erp_code'] ?? ''));
                $erpKey = mb_strtoupper($erpCode, 'UTF-8');
                $itemMeta = null;
                if ($itemId > 0 && isset($itemMaps['by_id'][$itemId])) {
                    $itemMeta = $itemMaps['by_id'][$itemId];
                } elseif ($erpKey !== '' && isset($itemMaps['by_erp'][$erpKey])) {
                    $itemMeta = $itemMaps['by_erp'][$erpKey];
                }
                $categoryName = is_array($itemMeta) ? trim((string)($itemMeta['category_name'] ?? '')) : '';

                return self::isProdutoAcabado($categoryName, $erpCode);
            }));
        }

        $needle = mb_strtolower($filter, 'UTF-8');

        return array_values(array_filter($prodRows, function (array $prod) use ($needle, $itemMaps): bool {
            $erpCode = trim((string)($prod['erp_code'] ?? ''));
            if ($erpCode !== '' && str_contains(mb_strtolower($erpCode, 'UTF-8'), $needle)) {
                return true;
            }

            $itemId = (int)($prod['inv_item_id'] ?? 0);
            $erpKey = mb_strtoupper($erpCode, 'UTF-8');
            $itemMeta = null;
            if ($itemId > 0 && isset($itemMaps['by_id'][$itemId])) {
                $itemMeta = $itemMaps['by_id'][$itemId];
            } elseif ($erpKey !== '' && isset($itemMaps['by_erp'][$erpKey])) {
                $itemMeta = $itemMaps['by_erp'][$erpKey];
            }

            $description = trim((string)($prod['description'] ?? ($itemMeta['description'] ?? '')));
            if ($description !== '' && str_contains(mb_strtolower($description, 'UTF-8'), $needle)) {
                return true;
            }

            $categoryName = is_array($itemMeta) ? trim((string)($itemMeta['category_name'] ?? '')) : '';

            return $categoryName !== '' && str_contains(mb_strtolower($categoryName, 'UTF-8'), $needle);
        }));
    }

    /**
     * @param list<string>|null $warehouseCodes
     * @param array<string, mixed>|null $criterionAggregation
     * @return array{
     *   by_id: array<int, array{share_criterion_4: float, share_criterion_6: float}>,
     *   by_erp: array<string, array{share_criterion_4: float, share_criterion_6: float}>
     * }
     */
    private function buildCriterionShareMaps(
        int $periodId,
        ?array $warehouseCodes,
        ?array $criterionAggregation = null
    ): array {
        $byId = [];
        $byErp = [];
        $aggregation = $criterionAggregation
            ?? (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId, $warehouseCodes);

        foreach ($aggregation['items'] ?? [] as $item) {
            $shares = [
                'share_criterion_4' => (float)($item['share_criterion_4'] ?? 0),
                'share_criterion_6' => (float)($item['share_criterion_6'] ?? 0),
            ];
            $id = (int)($item['inv_item_id'] ?? 0);
            if ($id > 0) {
                $byId[$id] = $shares;
            }
            $erp = mb_strtoupper(trim((string)($item['erp_code'] ?? '')), 'UTF-8');
            if ($erp !== '') {
                $byErp[$erp] = $shares;
            }
        }

        return ['by_id' => $byId, 'by_erp' => $byErp];
    }

    public static function isProdutoAcabado(string $categoryName, string $erpCode = ''): bool
    {
        $cat = mb_strtoupper(trim($categoryName), 'UTF-8');
        if ($cat === 'PA - PROJETO') {
            return false;
        }
        if ($cat !== '' && (str_contains($cat, 'ACAB') || str_contains($cat, 'PROD ACAB'))) {
            return true;
        }
        if ($erpCode !== '' && preg_match('/^43\d/u', $erpCode)) {
            return true;
        }

        return false;
    }
}
