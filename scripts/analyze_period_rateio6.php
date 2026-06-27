<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

use App\adms\Models\Services\InvCostCriterionDriversService;
use App\adms\Models\Services\InvCostPeriodProductionItemsService;
use App\adms\Models\Services\InvCostProductionAggregationService;

$periodId = (int)($argv[1] ?? 4);

$production = (new InvCostProductionAggregationService())->aggregateByPeriod($periodId);
$items = $production['items'] ?? [];
$paErp = 0;
$withEff = 0;
$noItem = 0;
foreach ($items as $row) {
    $erp = (string)($row['erp_code'] ?? '');
    if (preg_match('/^43\d/u', $erp)) {
        $paErp++;
    }
    if ((int)($row['inv_item_id'] ?? 0) <= 0) {
        $noItem++;
    }
    if (isset($row['efficiency_ratio']) && (float)$row['efficiency_ratio'] > 0) {
        $withEff++;
    }
}

$agg = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId);
$totals = $agg['totals'] ?? [];
$d6zero = 0;
$d6sum = 0.0;
foreach ($agg['items'] ?? [] as $row) {
    $d6 = (float)($row['driver_6'] ?? 0);
    $d6sum += $d6;
    if ($d6 <= 0) {
        $d6zero++;
    }
}

echo "Periodo {$periodId}\n";
echo 'SKUs com producao (erp distintos): ' . count($items) . "\n";
echo 'Codigos 43* (PA ERP): ' . $paErp . "\n";
echo 'Sem inv_item_id: ' . $noItem . "\n";
echo 'Com efficiency_ratio na agregacao: ' . $withEff . "\n";
echo 'Driver_6 total (criterio): ' . ($totals['driver_6'] ?? 0) . "\n";
echo 'Driver_6 soma manual: ' . round($d6sum, 4) . "\n";
echo 'SKUs driver_6 zero: ' . $d6zero . "\n";

$sample = (new InvCostPeriodProductionItemsService())->listForPeriod($periodId, null, '43000043');
if ($sample !== []) {
    $r = $sample[0];
    echo "\n43000043:\n";
    echo '  analyses: ' . ($r['analysis_count_total'] ?? '—') . "\n";
    echo '  driver_6: ' . ($r['driver_6'] ?? '—') . "\n";
    echo '  share_6: ' . ($r['share_criterion_6'] ?? '—') . "%\n";
    echo '  efficiency_ratio: ' . ($r['efficiency_ratio'] ?? '—') . "\n";
}
