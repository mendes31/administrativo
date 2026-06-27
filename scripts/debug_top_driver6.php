<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
use App\adms\Models\Services\InvCostCriterionDriversService;
$agg = (new InvCostCriterionDriversService())->aggregateAllCriteria(4);
$rows = $agg['items'] ?? [];
usort($rows, fn($a, $b) => (float)($b['driver_6'] ?? 0) <=> (float)($a['driver_6'] ?? 0));
foreach (array_slice($rows, 0, 10) as $r) {
    echo ($r['erp_code'] ?? '') . ' d6=' . ($r['driver_6'] ?? 0) . ' analyses=' . ($r['analysis_count_total'] ?? 0) . ' batches=' . ($r['batches_count'] ?? 0) . ' eff=' . ($r['efficiency_ratio'] ?? 'null') . PHP_EOL;
}
echo 'total=' . ($agg['totals']['driver_6'] ?? 0) . PHP_EOL;
