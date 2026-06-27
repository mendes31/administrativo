<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\inventory\InvCostAllocationRulesRepository;
use App\adms\Models\Repository\inventory\InvCostExpensePoolsRepository;

/**
 * Mapa conta DRE → critério sugerido (docs/dre_criterion_map_suggested.csv).
 */
class InvCostDreCriterionMapService
{
    /** @var array<string, array{criterion: int, weight_pct: float}>|null */
    private static ?array $mapCache = null;

    /**
     * @return array{applied: int, skipped: int, message: string}
     */
    public function applySuggestedCriteriaForPeriod(int $periodId, bool $onlyWithoutRules = true): array
    {
        if ($periodId <= 0) {
            return ['applied' => 0, 'skipped' => 0, 'message' => 'Período inválido.'];
        }

        $map = self::loadMap();
        if ($map === []) {
            return ['applied' => 0, 'skipped' => 0, 'message' => 'Mapa de critérios não encontrado.'];
        }

        $poolsRepo = new InvCostExpensePoolsRepository();
        $rulesRepo = new InvCostAllocationRulesRepository();
        $applied = 0;
        $skipped = 0;

        foreach ($poolsRepo->getByPeriodWithRules($periodId) as $pool) {
            $poolId = (int)($pool['id'] ?? 0);
            $code = trim((string)($pool['account_code'] ?? ''));
            if ($poolId <= 0 || $code === '') {
                continue;
            }

            if ($onlyWithoutRules && ($pool['rules'] ?? []) !== []) {
                $skipped++;
                continue;
            }

            if (InvCostEnergyRedistributionService::isEnergyAccountCode($code)
                && !InvCostEnergyRedistributionService::isAlreadySplitAccount($code)) {
                continue;
            }

            $entry = $map[$code] ?? $map[$this->normalizeAccountKey($code)] ?? null;
            if ($entry === null) {
                continue;
            }

            $rulesRepo->replaceRulesForPool($poolId, [
                ['criterion' => $entry['criterion'], 'weight_pct' => $entry['weight_pct']],
            ]);
            $applied++;
        }

        return [
            'applied' => $applied,
            'skipped' => $skipped,
            'message' => sprintf(
                'Critérios sugeridos aplicados em %d conta(s)%s.',
                $applied,
                $skipped > 0 ? " ({$skipped} já tinham critério)" : ''
            ),
        ];
    }

    /**
     * @return array<string, array{criterion: int, weight_pct: float}>
     */
    public static function loadMap(): array
    {
        if (self::$mapCache !== null) {
            return self::$mapCache;
        }

        $path = dirname(__DIR__, 3) . '/docs/dre_criterion_map_suggested.csv';
        if (!is_readable($path)) {
            self::$mapCache = [];

            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            self::$mapCache = [];

            return [];
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $map = [];
        $header = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $cols = str_getcsv($line, ';');
            if ($header === null) {
                $header = array_map(static fn(string $h): string => mb_strtolower(trim($h), 'UTF-8'), $cols);
                continue;
            }
            if (count($cols) < 3) {
                continue;
            }
            $code = trim((string)($cols[0] ?? ''));
            $criterion = (int)($cols[2] ?? 0);
            if ($code === '' || $criterion < 1 || $criterion > 8) {
                continue;
            }
            $map[$code] = ['criterion' => $criterion, 'weight_pct' => 100.0];
        }

        self::$mapCache = $map;

        return $map;
    }

    private function normalizeAccountKey(string $code): string
    {
        return ltrim(trim($code), '0') ?: '0';
    }
}
