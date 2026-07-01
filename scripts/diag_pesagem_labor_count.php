<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvItemRouteConsolidatedRepository;

$itemId = (int)(new InvItemsRepository())->findByErpCode('40500052')['id'];
foreach ((new InvItemRouteConsolidatedRepository())->getByItem($itemId) as $op) {
    if (stripos((string)($op['operation_name'] ?? ''), 'PESAGEM') === false) continue;
    echo 'consolidated id=' . ($op['id'] ?? '') . "\n";
    echo 'labor count=' . count($op['labor_lines'] ?? []) . "\n";
    foreach ($op['labor_lines'] ?? [] as $i => $l) {
        echo "  [$i] role={$l['inv_labor_role_id']} qty={$l['qty']} line_time=" . ($l['line_time_minutes'] ?? 'null') . "\n";
    }
    echo 'resource count=' . count($op['resource_lines'] ?? []) . "\n";
}
