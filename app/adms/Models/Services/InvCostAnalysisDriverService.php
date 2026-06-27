<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Drivers CQ/P&D/DA — critérios 4 e 6 (Pasta 10 / linhas 2307–2312).
 *
 * Critério 4: fator de complexidade (2 / 5 / 8).
 * Critério 6: fator × número de análises totais.
 * Nº análises = (nº linhas MP + nº linhas MAE/EMB na BOM) × lotes × eficiência do período.
 * Eficiência = qty produzida ÷ (lotes × lote mín.) — equivalente à linha 1088 × lotes da planilha.
 */
class InvCostAnalysisDriverService
{
    /** @var array<int, list<array<string, mixed>>> */
    private static array $materialLinesCache = [];

    /** @var array<string, float> */
    public const COMPLEXITY_WEIGHTS = [
        'baixa' => 2.0,
        'low' => 2.0,
        'media' => 5.0,
        'média' => 5.0,
        'medium' => 5.0,
        'alta' => 8.0,
        'high' => 8.0,
    ];

    /**
     * @param array<string, mixed>|null $periodItem
     */
    public function complexityFactor(?array $periodItem): float
    {
        $level = mb_strtolower(trim((string)($periodItem['complexity_level'] ?? 'media')), 'UTF-8');

        return self::COMPLEXITY_WEIGHTS[$level] ?? 5.0;
    }

    public function countMpLines(int $itemId): int
    {
        return $this->countBomLinesByGroup($itemId, ['Matéria Prima']);
    }

    public function countMaeLines(int $itemId): int
    {
        return $this->countBomLinesByGroup($itemId, ['Embalagens']);
    }

    /**
     * Número de análises totais no período (linha 2310).
     *
     * @param float|null $efficiencyRatio Produzido ÷ planejado (1,0 se omitido)
     */
    public function analysisCountTotal(int $itemId, int $batchesCount, ?float $efficiencyRatio = null): float
    {
        if ($itemId <= 0 || $batchesCount <= 0) {
            return 0.0;
        }

        $lineCount = $this->countMpLines($itemId) + $this->countMaeLines($itemId);
        if ($lineCount <= 0) {
            return 0.0;
        }

        $efficiency = $this->normalizeEfficiencyRatio($efficiencyRatio, null, null);

        return round($lineCount * $batchesCount * $efficiency, 4);
    }

    /**
     * Eficiência para análises: produzido ÷ planejado, limitada a valores plausíveis.
     */
    public function normalizeEfficiencyRatio(
        ?float $ratio,
        ?float $qtyProduced = null,
        ?float $qtyPlanned = null
    ): float {
        if ($qtyProduced !== null && $qtyPlanned !== null && $qtyProduced > 0 && $qtyPlanned > 0) {
            return min(max($qtyProduced / $qtyPlanned, 0.0), 2.0);
        }

        if ($ratio !== null && $ratio > 0 && $ratio <= 2.0) {
            return $ratio;
        }

        return 1.0;
    }

    /**
     * @deprecated Mantido para diagnóstico; não entra no critério 6.
     */
    public function sumMpMaeQtyPerBatch(int $itemId): float
    {
        if ($itemId <= 0) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($this->materialLines($itemId) as $line) {
            $group = (string)($line['group_name'] ?? '');
            if ($group !== 'Matéria Prima' && $group !== 'Embalagens') {
                continue;
            }
            $sum += (float)($line['effective_qty'] ?? $line['quantity'] ?? 0);
        }

        return round($sum, 6);
    }

    /**
     * Linhas MP + MAE por lote (base do critério 6 antes de lotes × eficiência).
     */
    public function analysisLinesPerBatch(int $itemId): int
    {
        if ($itemId <= 0) {
            return 0;
        }

        return $this->countMpLines($itemId) + $this->countMaeLines($itemId);
    }

    /**
     * Número de análises totais no período (linha 2310) — versão legada por qty.
     */
    public function analysisCountTotalByQty(int $itemId, int $batchesCount): float
    {
        if ($itemId <= 0 || $batchesCount <= 0) {
            return 0.0;
        }

        $perBatch = $this->sumMpMaeQtyPerBatch($itemId);
        if ($perBatch <= 0) {
            $lineCount = $this->countMpLines($itemId) + $this->countMaeLines($itemId);
            if ($lineCount <= 0) {
                return 0.0;
            }
            $perBatch = (float)$lineCount;
        }

        return round($perBatch * $batchesCount, 4);
    }

    /**
     * Driver critério 6 = fator complexidade × nº análises (linha 2311).
     *
     * @param array<string, mixed>|null $periodItem
     */
    public function driver6(int $itemId, int $batchesCount, ?array $periodItem, ?float $efficiencyRatio = null): float
    {
        $factor = $this->complexityFactor($periodItem);
        $analyses = $this->analysisCountTotal($itemId, $batchesCount, $efficiencyRatio);

        return round($factor * $analyses, 6);
    }

    /**
     * @param array<string, mixed>|null $periodItem
     * @return array{
     *   complexity_factor: float,
     *   mp_lines: int,
     *   mae_lines: int,
     *   analysis_lines_per_batch: int,
     *   qty_mp_mae_per_batch: float,
     *   analysis_count_total: float,
     *   driver_4: float,
     *   driver_6: float
     * }
     */
    public function metricsForItem(
        int $itemId,
        int $batchesCount,
        ?array $periodItem,
        ?float $efficiencyRatio = null
    ): array {
        $factor = $this->complexityFactor($periodItem);
        $mpLines = $itemId > 0 ? $this->countMpLines($itemId) : 0;
        $maeLines = $itemId > 0 ? $this->countMaeLines($itemId) : 0;
        $linesPerBatch = $mpLines + $maeLines;
        $qtyPerBatch = $itemId > 0 ? $this->sumMpMaeQtyPerBatch($itemId) : 0.0;
        $analysisTotal = $itemId > 0 ? $this->analysisCountTotal($itemId, $batchesCount, $efficiencyRatio) : 0.0;

        return [
            'complexity_factor' => $factor,
            'mp_lines' => $mpLines,
            'mae_lines' => $maeLines,
            'analysis_lines_per_batch' => $linesPerBatch,
            'qty_mp_mae_per_batch' => $qtyPerBatch,
            'analysis_count_total' => $analysisTotal,
            'driver_4' => $factor,
            'driver_6' => round($factor * $analysisTotal, 6),
        ];
    }

    /**
     * @param list<string> $groups
     */
    private function countBomLinesByGroup(int $itemId, array $groups): int
    {
        $count = 0;
        foreach ($this->materialLines($itemId) as $line) {
            if (in_array((string)($line['group_name'] ?? ''), $groups, true)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function materialLines(int $itemId): array
    {
        if ($itemId <= 0) {
            return [];
        }

        if (!isset(self::$materialLinesCache[$itemId])) {
            self::$materialLinesCache[$itemId] = InventoryCostService::calculateBreakdown($itemId, [])['materials'] ?? [];
        }

        return self::$materialLinesCache[$itemId];
    }
}
