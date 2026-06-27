<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InvCostAnalysisDriverService;
use App\adms\Models\Services\InvCostCriterionDriversService;
use App\adms\Models\Services\InventoryCostService;

$erpCode = $argv[1] ?? '43000043';
$batches = (int)($argv[2] ?? 56);
$periodId = (int)($argv[3] ?? 4);

$repo = new InvItemsRepository();
$item = $repo->findByErpCode($erpCode);
if ($item === null) {
    fwrite(STDERR, "Item {$erpCode} not found\n");
    exit(1);
}

$itemId = (int)$item['id'];
$itemFull = $repo->getOne($itemId) ?: [];
echo "ERP: {$erpCode} | ID: {$itemId} | complexity: " . ($itemFull['complexity_level'] ?? '—') . "\n";
echo "standard_batch_size: " . ($itemFull['standard_batch_size'] ?? '—') . "\n\n";

$bd = InventoryCostService::calculateBreakdown($itemId, []);
$mpLines = 0;
$maeLines = 0;
$sumQty = 0.0;

foreach ($bd['materials'] as $line) {
    $group = (string)($line['group_name'] ?? '');
    if ($group !== 'Matéria Prima' && $group !== 'Embalagens') {
        continue;
    }
    if ($group === 'Matéria Prima') {
        $mpLines++;
    } else {
        $maeLines++;
    }
    $sumQty += (float)($line['effective_qty'] ?? 0);
    printf(
        "  [%s] %s | qty=%s scrap=%s%% eff=%s\n",
        $group,
        (string)($line['component_code'] ?? 'MANUAL') . ' ' . mb_substr((string)($line['component_description'] ?? ''), 0, 40),
        $line['quantity'] ?? 0,
        $line['scrap_percent'] ?? 0,
        $line['effective_qty'] ?? 0
    );
}

echo "\nMP lines: {$mpLines}\n";
echo "MAE lines: {$maeLines}\n";
echo "Sum effective qty/batch: {$sumQty}\n";
echo "Lines × batches ({$batches}): " . (($mpLines + $maeLines) * $batches) . "\n";
echo "Qty sum × batches: " . round($sumQty * $batches, 4) . "\n";

$svc = new InvCostAnalysisDriverService();
$metrics = $svc->metricsForItem($itemId, $batches, [
    'complexity_level' => $itemFull['complexity_level'] ?? 'media',
], 82417 / 84000);
echo "\nSystem metrics:\n";
foreach ($metrics as $k => $v) {
    echo "  {$k}: {$v}\n";
}

if ($periodId > 0) {
    $agg = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId);
    $totals = $agg['totals'] ?? [];
    echo "\nPeriod {$periodId} totals:\n";
    echo "  driver_4 total: " . ($totals['driver_4'] ?? 0) . "\n";
    echo "  driver_6 total: " . ($totals['driver_6'] ?? 0) . "\n";
    foreach ($agg['items'] ?? [] as $row) {
        if ((int)($row['inv_item_id'] ?? 0) === $itemId) {
            echo "\nShares for this SKU:\n";
            echo "  share_criterion_4: " . ($row['share_criterion_4'] ?? 0) . "%\n";
            echo "  share_criterion_6: " . ($row['share_criterion_6'] ?? 0) . "%\n";
            break;
        }
    }
}

echo "\nSpreadsheet reference (43000043): analyses=1098.9, driver6=2197.8, rateio4=0.4%, rateio6=4.7%\n";
echo "Per batch from spreadsheet: " . round(1098.9 / $batches, 4) . "\n";
