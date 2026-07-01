<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvItemRouteConsolidatedRepository;

require_once __DIR__ . '/../app/adms/Views/inventory/partials/operation_metrics.php';

$erp = $argv[1] ?? '40500052';
$item = (new InvItemsRepository())->findByErpCode($erp);
$itemId = (int)$item['id'];
$ops = (new InvItemRouteConsolidatedRepository())->getByItem($itemId);

foreach ($ops as $op) {
    if (stripos((string)($op['operation_name'] ?? ''), 'PESAGEM') === false) {
        continue;
    }
    echo "=== PESAGEM ===\n";
    echo 'time=' . ($op['time_per_batch_hours'] ?? '') . ' unit=' . ($op['time_unit'] ?? '') . "\n";
    $labor = invNormalizeLaborLinesForDrivers($op['labor_lines'] ?? []);
    $res = invNormalizeMachineResourceLinesForDrivers($op['resource_lines'] ?? []);
    $metrics = invOperationMetrics($op);
    $drivers = invOperationDriverHours($labor, $res, (float)$metrics['time_minutes'], 1);
    echo 'time_minutes=' . $metrics['time_minutes'] . "\n";
    echo 'HH=' . $drivers['labor_hours'] . ' HM=' . $drivers['machine_hours'] . "\n";
    $singleLabor = array_slice($op['labor_lines'] ?? [], 0, 1);
    $d2 = invOperationDriverHours(invNormalizeLaborLinesForDrivers($singleLabor), [], (float)$metrics['time_minutes'], 1);
    echo 'HH (1 labor row only)=' . $d2['labor_hours'] . "\n";
    echo "labor_lines:\n";
    foreach ($op['labor_lines'] ?? [] as $l) {
        echo '  role=' . ($l['inv_labor_role_id'] ?? '') . ' qty=' . ($l['qty'] ?? '') . ' line_time=' . ($l['line_time_minutes'] ?? 'null') . ' type=' . ($l['resource_type'] ?? '') . "\n";
    }
    echo "resource_lines:\n";
    foreach ($op['resource_lines'] ?? [] as $r) {
        echo '  res=' . ($r['inv_production_resource_id'] ?? '') . ' qty=' . ($r['qty'] ?? '') . ' type=' . ($r['resource_type'] ?? '') . ' erp=' . ($r['resource_erp_code'] ?? '') . "\n";
    }
}
