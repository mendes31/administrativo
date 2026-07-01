<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Helpers\InvRoutePiExplosionHelper;
use App\adms\Models\Repository\inventory\InvItemBomRepository;
use App\adms\Models\Repository\inventory\InvItemRouteConsolidatedRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;

$erp = $argv[1] ?? '41000052';
$item = (new InvItemsRepository())->findByErpCode($erp);
if ($item === null) {
    fwrite(STDERR, "Item not found\n");
    exit(1);
}
$itemId = (int)$item['id'];

echo "=== BOM DISPLAY — {$erp} ===\n";
foreach ((new InvItemBomRepository())->getDisplayRowsByItem($itemId) as $row) {
    $kind = (string)($row['bom_row_kind'] ?? '?');
    $code = (string)($row['component_code'] ?? $row['manual_description'] ?? '');
    $qty = (float)($row['quantity_per_batch'] ?? 0);
    $cost = \App\adms\Models\Repository\inventory\InvItemBomRepository::computeLineMaterialCost($row);
    $via = $kind === 'pi_exploded' ? ' via PI ' . ($row['from_pi_code'] ?? '') : '';
    echo sprintf("%-12s %-40s qty=%.4f custo=%.4f%s\n", $kind, mb_substr($code, 0, 40), $qty, $cost, $via);
}

echo "\n=== ROTA CONSOLIDADA — {$erp} ===\n";
foreach ((new InvItemRouteConsolidatedRepository())->getByItem($itemId) as $op) {
    $pi = InvRoutePiExplosionHelper::parseSourcePiCode((string)($op['notes'] ?? ''));
    $tag = $pi !== '' ? " [PI {$pi}]" : '';
    echo sprintf(
        "seq=%d %-30s %s%s\n",
        (int)($op['sequence'] ?? 0),
        mb_substr((string)($op['operation_name'] ?? ''), 0, 30),
        strtoupper((string)($op['time_unit'] ?? 'MIN')),
        $tag
    );
}
