<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Services\InvCostCriterionDriversService;
use App\adms\Models\Services\InvCostPeriodDriversService;

$periodId = (int)($argv[1] ?? 4);

$pd = (new InvCostPeriodDriversService())->aggregateDriversByPeriod($periodId);
$cd = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId);

echo "Total HH período: " . ($pd['total_hh_period'] ?? 0) . " h\n";
echo "Total HM período: " . ($pd['total_hm_period'] ?? 0) . " h\n";
echo "SKUs com driver: " . count($pd['items'] ?? []) . "\n\n";

$withHh = 0;
$zeroHh = 0;
$top = $pd['items'] ?? [];
usort($top, static fn($a, $b) => (float)($b['hh_period'] ?? 0) <=> (float)($a['hh_period'] ?? 0));

foreach ($top as $row) {
    $hh = (float)($row['hh_period'] ?? 0);
    if ($hh > 0) {
        $withHh++;
    } else {
        $zeroHh++;
    }
}
echo "SKUs HH>0: {$withHh}  HH=0: {$zeroHh}\n\n";
echo "Top 10 HH período:\n";
foreach (array_slice($top, 0, 10) as $row) {
    echo sprintf(
        "  %s  hh=%.2f  hh/lote=%.2f  lotes=%.2f  qtd=%.0f\n",
        $row['erp_code'] ?? '?',
        (float)($row['hh_period'] ?? 0),
        (float)($row['hh_per_batch'] ?? 0),
        (float)($row['batches_produced'] ?? 0),
        (float)($row['qty_produced'] ?? 0)
    );
}

echo "\nTotais critérios:\n";
foreach ($cd['totals'] ?? [] as $k => $v) {
    echo "  {$k}: {$v}\n";
}
