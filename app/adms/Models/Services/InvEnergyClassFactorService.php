<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\InvCostEnergyClassHelper;
use App\adms\Models\Repository\inventory\InvEnergyClassFactorsRepository;

class InvEnergyClassFactorService
{
    /** @var array<string, float>|null */
    private static ?array $multiplierMap = null;

    /** @var list<array<string, mixed>>|null */
    private static ?array $rowsCache = null;

    public static function clearCache(): void
    {
        self::$multiplierMap = null;
        self::$rowsCache = null;
    }

    /**
     * @return array<string, float>
     */
    public static function multiplierMap(): array
    {
        if (self::$multiplierMap !== null) {
            return self::$multiplierMap;
        }

        try {
            $map = (new InvEnergyClassFactorsRepository())->getMultiplierMap();
        } catch (\Throwable) {
            $map = [];
        }

        if ($map === []) {
            $map = [
                InvCostEnergyClassHelper::CLASS_NA => 0.0,
                InvCostEnergyClassHelper::CLASS_OTHER => 1.0,
                InvCostEnergyClassHelper::CLASS_CM => 1.2,
                InvCostEnergyClassHelper::CLASS_PROB => 1.2,
            ];
        }

        self::$multiplierMap = $map;

        return self::$multiplierMap;
    }

    public static function getMultiplier(string $code): float
    {
        $code = InvCostEnergyClassHelper::normalize($code) ?? InvCostEnergyClassHelper::CLASS_NA;
        $map = self::multiplierMap();

        return (float)($map[$code] ?? $map[InvCostEnergyClassHelper::CLASS_NA] ?? 0.0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listForAdmin(): array
    {
        if (self::$rowsCache !== null) {
            return self::$rowsCache;
        }

        try {
            self::$rowsCache = (new InvEnergyClassFactorsRepository())->listAll();
        } catch (\Throwable) {
            self::$rowsCache = [];
        }

        return self::$rowsCache;
    }

    /**
     * @param array<int|string, mixed> $input factors[id][multiplier|label|notes]
     */
    public static function saveFromForm(array $input): void
    {
        $repo = new InvEnergyClassFactorsRepository();
        $allowedIds = [];
        foreach ($repo->listAll() as $row) {
            $allowedIds[(int)($row['id'] ?? 0)] = true;
        }

        $updates = [];
        foreach ($input as $rawId => $rawData) {
            if (!is_array($rawData)) {
                continue;
            }
            $id = (int)$rawId;
            if ($id <= 0 || !isset($allowedIds[$id])) {
                continue;
            }

            $multRaw = $rawData['multiplier'] ?? 1;
            if (is_string($multRaw)) {
                $multRaw = str_replace(',', '.', trim($multRaw));
            }
            $multiplier = is_numeric($multRaw) ? max(0.0, (float)$multRaw) : 1.0;

            $updates[$id] = [
                'multiplier' => round($multiplier, 4),
                'label' => $rawData['label'] ?? null,
                'notes' => $rawData['notes'] ?? null,
            ];
        }

        $repo->updateMultipliers($updates);
        self::clearCache();
    }
}
