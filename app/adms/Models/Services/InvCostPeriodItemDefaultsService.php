<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\InvCostBatchAdoptedHelper;
use App\adms\Helpers\InvCostComplexityHelper;
use App\adms\Helpers\InvCostEnergyClassHelper;
use App\adms\Helpers\InvCostProductionLineHelper;
use App\adms\Models\Repository\inventory\InvItemsRepository;

/**
 * Valores sugeridos para inv_cost_period_items quando não há override manual.
 */
class InvCostPeriodItemDefaultsService
{
    /**
     * @param array<string, mixed>|null $savedRow
     * @return array<string, mixed>
     */
    public function mergeWithDefaults(int $itemId, ?array $savedRow = null, ?array $itemRow = null): array
    {
        $defaults = $this->defaultsForItem($itemId, $itemRow);
        if ($savedRow === null) {
            return $defaults;
        }

        return [
            'energy_class' => $defaults['energy_class'],
            'complexity_level' => $defaults['complexity_level'],
            'analysis_count' => isset($savedRow['analysis_count'])
                ? max(0, (int)$savedRow['analysis_count'])
                : (int)$defaults['analysis_count'],
            'batch_size_adopted' => $savedRow['batch_size_adopted'] ?? $defaults['batch_size_adopted'],
            'batch_size_theoretical' => self::nullableSavedFixedBatch($savedRow['batch_size_theoretical'] ?? null)
                ?? $defaults['batch_size_theoretical'],
            'qty_avg_per_round' => self::nullableSavedDecimal($savedRow['qty_avg_per_round'] ?? null)
                ?? $defaults['qty_avg_per_round'],
            'production_line' => InvCostProductionLineHelper::normalize($savedRow['production_line'] ?? null)
                ?? $defaults['production_line'],
            'sale_price_net' => $savedRow['sale_price_net'] ?? $defaults['sale_price_net'],
            'target_margin_pct' => $savedRow['target_margin_pct'] ?? $defaults['target_margin_pct'],
            'efficiency_pct' => $savedRow['efficiency_pct'] ?? $defaults['efficiency_pct'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultsForItem(int $itemId, ?array $prefetchedItem = null): array
    {
        $empty = [
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

        if ($itemId <= 0) {
            return $empty;
        }

        $item = $prefetchedItem;
        if (!is_array($item)) {
            $item = (new InvItemsRepository())->getOne($itemId);
        }
        if ($item === false || !is_array($item)) {
            return $empty;
        }

        $desc = mb_strtoupper(trim((string)($item['description'] ?? '')), 'UTF-8');
        $category = mb_strtoupper(trim((string)($item['category_name'] ?? '')), 'UTF-8');
        $batchSize = (float)($item['standard_batch_size'] ?? 0);
        $energyClass = InvCostEnergyClassHelper::resolveForCosting($item['energy_class'] ?? null);
        $complexity = InvCostComplexityHelper::resolveForCosting($item['complexity_level'] ?? null);

        return [
            'energy_class' => $energyClass,
            'complexity_level' => $complexity,
            'analysis_count' => 0,
            'batch_size_adopted' => null,
            'batch_size_theoretical' => InvCostBatchAdoptedHelper::theoreticalFixedFromCatalog($batchSize),
            'qty_avg_per_round' => null,
            'production_line' => $this->nonEmptyString($item['production_line'] ?? null)
                ? InvCostProductionLineHelper::normalize($item['production_line'] ?? null)
                : null,
            'sale_price_net' => null,
            'target_margin_pct' => null,
            'efficiency_pct' => null,
        ];
    }

    public function suggestEnergyClass(string $description, string $categoryName = ''): string
    {
        $text = $description . ' ' . $categoryName;

        if (preg_match('/\b(PROB|PROBIOT|PROBIÓT)\b/u', $text)) {
            return 'PROB';
        }
        if (preg_match('/\b(CAPS\s*MOLE|CAPSULA\s*MOLE|CM\b|CÁPSULA\s*MOLE)\b/u', $text)) {
            return 'CM';
        }
        if (preg_match('/\b(CAPS|CÁPS|COMPRIM|CAPSULA)\b/u', $text)) {
            return 'OTHER';
        }

        return '';
    }

    private function nonEmptyString(mixed $value): ?string
    {
        $s = trim((string)($value ?? ''));

        return $s !== '' ? $s : null;
    }

    private static function nullableSavedDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $f = (float)$value;

        return $f > 0 ? round($f, 4) : null;
    }

    /** Lote teórico salvo no período: só valores &gt; 1 (0/1 = placeholder, ignora). */
    private static function nullableSavedFixedBatch(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return InvCostBatchAdoptedHelper::theoreticalFixedFromCatalog((float)$value);
    }
}
