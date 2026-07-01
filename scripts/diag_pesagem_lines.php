<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvItemRouteConsolidatedRepository;

$erp = $argv[1] ?? '40500052';
$item = (new InvItemsRepository())->findByErpCode($erp);
$itemId = (int)$item['id'];

echo "=== SAP ROUTE PESAGEM ===\n";
foreach ((new InvItemOperationsRepository())->getByItem($itemId) as $op) {
    if (stripos((string)($op['operation_name'] ?? ''), 'PESAGEM') === false) continue;
    echo 'time=' . ($op['time_per_batch_hours'] ?? '') . ' unit=' . ($op['time_unit'] ?? '') . ' operators=' . ($op['operators_qty'] ?? '') . "\n";
    foreach ($op['resource_lines'] ?? [] as $r) {
        echo '  res id=' . ($r['inv_production_resource_id'] ?? '') . ' erp=' . ($r['resource_erp_code'] ?? '') . ' type=' . ($r['resource_type'] ?? '') . ' qty=' . ($r['qty'] ?? '') . "\n";
    }
    foreach ($op['labor_lines'] ?? [] as $l) {
        echo '  labor role=' . ($l['inv_labor_role_id'] ?? '') . ' name=' . ($l['role_name'] ?? '') . ' qty=' . ($l['qty'] ?? '') . "\n";
    }
}

echo "\n=== CONSOLIDATED PESAGEM (raw) ===\n";
foreach ((new InvItemRouteConsolidatedRepository())->getByItem($itemId) as $op) {
    if (stripos((string)($op['operation_name'] ?? ''), 'PESAGEM') === false) continue;
    echo 'time=' . ($op['time_per_batch_hours'] ?? '') . ' unit=' . ($op['time_unit'] ?? '') . "\n";
    foreach ($op['resource_lines'] ?? [] as $r) {
        echo '  res id=' . ($r['inv_production_resource_id'] ?? '') . ' erp=' . ($r['resource_erp_code'] ?? '') . ' type=' . ($r['resource_type'] ?? '') . ' qty=' . ($r['qty'] ?? '') . ' line_time=' . ($r['line_time_minutes'] ?? 'null') . "\n";
    }
    foreach ($op['labor_lines'] ?? [] as $l) {
        echo '  labor role=' . ($l['inv_labor_role_id'] ?? '') . ' name=' . ($l['role_name'] ?? '') . ' qty=' . ($l['qty'] ?? '') . ' line_time=' . ($l['line_time_minutes'] ?? 'null') . "\n";
    }
}
