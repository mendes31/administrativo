<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvItemRouteConsolidatedRepository;

$itemId = (int)(new InvItemsRepository())->findByErpCode('40500052')['id'];
$totalLabor = 0;
$totalResource = 0;
foreach ((new InvItemRouteConsolidatedRepository())->getByItem($itemId) as $op) {
    $lc = count($op['labor_lines'] ?? []);
    $rc = count($op['resource_lines'] ?? []);
    $totalLabor += $lc;
    $totalResource += $rc;
    echo ($op['operation_name'] ?? '') . " labor=$lc resource=$rc\n";
}
echo "TOTAL labor rows=$totalLabor resource rows=$totalResource\n";
