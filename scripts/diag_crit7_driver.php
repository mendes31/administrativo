<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Helpers\InvCostProductionLineHelper;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Models\Services\InvCostCriterionDriversService;
use App\adms\Models\Services\InvCostPeriodItemDefaultsService;
use App\adms\Models\Services\InvCostVariableEnergyService;
use App\adms\Models\Services\InventoryCostService;

$periodId = (int)($argv[1] ?? 4);

$period = (new InvCostPeriodsRepository())->getOne($periodId);
$agg = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId);

echo 'Período #' . $periodId . ' | kwh_tariff: ' . ($period['kwh_tariff'] ?? 'null') . PHP_EOL;
echo 'total driver_7: ' . ($agg['totals']['driver_7'] ?? 0) . PHP_EOL;
echo 'total driver_3 (HM): ' . ($agg['totals']['driver_3'] ?? 0) . PHP_EOL;

$defaults = new InvCostPeriodItemDefaultsService();
$energy = new InvCostVariableEnergyService();

$stats = [
    'tiaraju' => 0,
    'tiaraju_hm' => 0,
    'tiaraju_driver' => 0,
    'no_kwh_batch' => 0,
    'terceiro' => 0,
    'no_line' => 0,
];

$samples = [];

foreach ($agg['items'] ?? [] as $r) {
    $itemId = (int)($r['inv_item_id'] ?? 0);
    if ($itemId <= 0) {
        continue;
    }

    $pi = $defaults->mergeWithDefaults($itemId, null);
    $line = InvCostProductionLineHelper::fromPeriodItem($pi);

    if ($line === InvCostProductionLineHelper::LINE_TIARAJU) {
        $stats['tiaraju']++;
        if ((float)($r['hm_period'] ?? 0) > 0) {
            $stats['tiaraju_hm']++;
        }
        if ((float)($r['driver_7'] ?? 0) > 0) {
            $stats['tiaraju_driver']++;
        } else {
            $kwh = $energy->computeKwhPerBatchForItem($itemId);
            $hmBatch = (float)(InventoryCostService::calculateBreakdown($itemId, [])['machine_hours'] ?? 0);
            if ($kwh <= 0) {
                $stats['no_kwh_batch']++;
                if (count($samples) < 5) {
                    $samples[] = [
                        'erp' => $r['erp_code'] ?? '',
                        'hm_period' => $r['hm_period'] ?? 0,
                        'hm_batch' => $hmBatch,
                        'kwh_batch' => $kwh,
                    ];
                }
            }
        }
    } elseif ($line === InvCostProductionLineHelper::LINE_TERCEIRO) {
        $stats['terceiro']++;
    } else {
        $stats['no_line']++;
    }
}

echo 'Stats: ' . json_encode($stats, JSON_UNESCAPED_UNICODE) . PHP_EOL;
if ($samples !== []) {
    echo "Amostras TIARAJU sem kWh/lote:\n";
    foreach ($samples as $s) {
        echo "  {$s['erp']} | hm_period={$s['hm_period']} hm_batch={$s['hm_batch']} kwh_batch={$s['kwh_batch']}\n";
    }
}
