<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Pasta 4 — tamanho de lote adotado (planilha linhas 24–27).
 *
 * F24 = lote teórico fixado no cadastro do item (`standard_batch_size` > 1).
 *       Valores 0 ou 1 (placeholder da sync SAP sem MinOrdrQty) = não fixado (F24=0).
 * F25 = qty média por rodada — cadastro do período ou, se ausente, total produzido ÷ nº de entradas
 *       de produção no período (coluna Lotes = COUNT(*) em lotes produzidos; não usa batch_number SAP distinto).
 * F26 = SE(F24=0; F25; F24)
 * F27 = qty produzida ÷ F26
 */
final class InvCostBatchAdoptedHelper
{
    /** Lote padrão (mín.) do item é “fixado” só quando > 1 (sync SAP grava 1 se vazio). */
    public static function isCatalogBatchFixed(mixed $standardBatchSize): bool
    {
        if (!is_numeric($standardBatchSize)) {
            return false;
        }

        return (float)$standardBatchSize > 1.0;
    }

    public static function theoreticalFixedFromCatalog(mixed $standardBatchSize): ?float
    {
        if (!self::isCatalogBatchFixed($standardBatchSize)) {
            return null;
        }

        return round((float)$standardBatchSize, 4);
    }

    /** Valor para exibição na coluna Lote SAP (0/1 = não fixado → null). */
    public static function catalogBatchForDisplay(mixed $standardBatchSize): ?float
    {
        return self::theoreticalFixedFromCatalog($standardBatchSize);
    }

    /**
     * Nº de lotes/rodadas no período (F25): entradas em lotes produzidos (COUNT(*)), mesma base da coluna Lotes.
     */
    public static function resolvePhysicalBatchesCount(int $entriesCount): int
    {
        return max(1, $entriesCount);
    }

    /**
     * F24: override do período > cadastro do item (> 1).
     *
     * @param array<string, mixed>|null $periodItem
     */
    public static function resolveTheoreticalFixed(?array $periodItem, mixed $catalogStandardBatchSize = null): ?float
    {
        $fromPeriod = self::theoreticalFixedFromCatalog($periodItem['batch_size_theoretical'] ?? null);
        if ($fromPeriod !== null) {
            return $fromPeriod;
        }

        return self::theoreticalFixedFromCatalog($catalogStandardBatchSize);
    }
    /**
     * @param array<string, mixed>|null $periodItem
     * @return array{
     *   batch_size_theoretical_fixed: ?float,
     *   qty_avg_per_round: ?float,
     *   batch_size_adopted: float,
     *   batches_produced: float,
     *   qty_theoretical: float,
     *   efficiency_ratio: float,
     *   efficiency_pct: float
     * }
     */
    public static function resolveProductionContext(
        ?array $periodItem,
        float $qtyProduced,
        int $physicalBatchesCount,
        mixed $catalogStandardBatchSize = null
    ): array {
        $theoreticalFixed = self::resolveTheoreticalFixed($periodItem, $catalogStandardBatchSize);
        $avgFromCadastro = self::toPositiveFloat($periodItem['qty_avg_per_round'] ?? null);
        $physicalBatches = max(1, $physicalBatchesCount);

        $avgPerRound = $avgFromCadastro;
        if ($avgPerRound === null && $qtyProduced > 0) {
            $avgPerRound = round($qtyProduced / $physicalBatches, 4);
        }

        $adopted = self::resolveAdoptedBatch($theoreticalFixed, $avgPerRound, $qtyProduced);
        $batchesProduced = self::batchesProduced($qtyProduced, $adopted);
        $qtyPlanned = self::resolveQtyPlanned($theoreticalFixed, $physicalBatches, $adopted);

        $efficiencyRatio = self::resolveEfficiencyRatio(
            $qtyProduced,
            $theoreticalFixed,
            $qtyPlanned
        );

        return [
            'batch_size_theoretical_fixed' => $theoreticalFixed,
            'qty_avg_per_round' => $avgPerRound,
            'batch_size_adopted' => $adopted,
            'batches_produced' => $batchesProduced,
            'qty_planned' => $qtyPlanned,
            'qty_theoretical' => $qtyPlanned,
            'efficiency_ratio' => $efficiencyRatio,
            'efficiency_pct' => round($efficiencyRatio * 100, 2),
        ];
    }

    public static function resolveAdoptedBatch(
        ?float $theoreticalFixed,
        ?float $avgQtyPerRound,
        float $qtyProduced
    ): float {
        if ($theoreticalFixed !== null && $theoreticalFixed > 0) {
            return round($theoreticalFixed, 4);
        }

        if ($avgQtyPerRound !== null && $avgQtyPerRound > 0) {
            return round($avgQtyPerRound, 4);
        }

        if ($qtyProduced > 0) {
            return round($qtyProduced, 4);
        }

        return 1.0;
    }

    public static function batchesProduced(float $qtyProduced, float $adoptedBatchSize): float
    {
        if ($qtyProduced <= 0 || $adoptedBatchSize <= 0) {
            return 0.0;
        }

        return round($qtyProduced / $adoptedBatchSize, 6);
    }

    public static function resolveQtyPlanned(
        ?float $theoreticalFixed,
        int $physicalBatches,
        float $adoptedBatchSize
    ): float {
        $physicalBatches = max(1, $physicalBatches);

        if ($theoreticalFixed !== null && $theoreticalFixed > 0) {
            return round($theoreticalFixed * $physicalBatches, 4);
        }

        if ($adoptedBatchSize > 0) {
            return round($adoptedBatchSize * $physicalBatches, 4);
        }

        return 0.0;
    }

    public static function qtyTheoretical(float $adoptedBatchSize, float $batchesProduced): float
    {
        if ($adoptedBatchSize <= 0 || $batchesProduced <= 0) {
            return 0.0;
        }

        return round($adoptedBatchSize * $batchesProduced, 4);
    }

    private static function resolveEfficiencyRatio(
        float $qtyProduced,
        ?float $theoreticalFixed,
        float $qtyPlanned
    ): float {
        if ($qtyProduced <= 0) {
            return 1.0;
        }

        if ($theoreticalFixed !== null && $theoreticalFixed > 0 && $qtyPlanned > 0) {
            return round(min(max($qtyProduced / $qtyPlanned, 0.0), 2.0), 6);
        }

        if ($qtyPlanned > 0) {
            return round(min(max($qtyProduced / $qtyPlanned, 0.0), 2.0), 6);
        }

        return 1.0;
    }

    private static function toPositiveFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $f = (float)$value;

        return $f > 0 ? $f : null;
    }
}
