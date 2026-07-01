<?php
require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvCostExpensePoolsRepository;
use App\adms\Models\Services\InvCostFixedAllocationEngine;
use App\adms\Models\Services\InvCostCriterionDriversService;

$periodId = 4;
$itemId = 4285;

$pools = (new InvCostExpensePoolsRepository())->getByPeriodWithRules($periodId);
$totalExpense = array_sum(array_map(fn($p) => (float)($p['amount'] ?? 0), $pools));
echo "Total DRE: " . number_format($totalExpense, 2, ',', '.') . "\n\n";

$criteria = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId);
$item = null;
foreach ($criteria['items'] as $row) {
    if ((int)($row['inv_item_id'] ?? 0) === $itemId) {
        $item = $row;
        break;
    }
}
if ($item) {
    echo "SKU 40500055 drivers:\n";
    for ($k = 1; $k <= 8; $k++) {
        echo "  crit $k driver=" . ($item['driver_' . $k] ?? 0) . " share=" . ($item['share_criterion_' . $k] ?? 0) . "%\n";
    }
}

$alloc = (new InvCostFixedAllocationEngine())->allocateForItem($periodId, $itemId);
$byCrit = [];
foreach ($alloc['details'] ?? [] as $d) {
    $c = (int)($d['criterion'] ?? 0);
    $byCrit[$c] = ($byCrit[$c] ?? 0) + (float)($d['allocated'] ?? 0);
}
ksort($byCrit);
echo "\nCFIX por critério:\n";
foreach ($byCrit as $c => $v) {
    echo sprintf("  crit %d: R$ %s (%.1f%%)\n", $c, number_format($v, 2, ',', '.'), 100 * $v / max($alloc['cfix_total'], 1));
}

echo "\nPools crit 8:\n";
foreach ($pools as $p) {
  $rules = $p['rules'] ?? [];
  foreach ($rules as $r) {
    if ((int)($r['criterion'] ?? 0) === 8) {
      echo "  {$p['account_code']} amt=" . number_format((float)$p['amount'], 2, ',', '.') . "\n";
    }
  }
}
