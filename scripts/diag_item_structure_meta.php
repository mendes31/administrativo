<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemBomRepository;
use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;

$erp = $argv[1] ?? '60500005';
$item = (new InvItemsRepository())->findByErpCode($erp);
if ($item === null) {
    fwrite(STDERR, "Item not found\n");
    exit(1);
}
$id = (int)$item['id'];
echo "Item {$erp} id={$id}\n";
echo "active=" . ($item['active'] ?? '?') . " category_id=" . ($item['inv_category_id'] ?? '?') . "\n";
echo "sap_bom_hash=" . substr((string)($item['sap_bom_hash'] ?? ''), 0, 16) . "...\n";
echo "sap_route_hash=" . substr((string)($item['sap_route_hash'] ?? ''), 0, 16) . "...\n";
echo "beas_version=" . ($item['sap_beas_version'] ?? 'null') . "\n";
echo "BOM lines: " . count((new InvItemBomRepository())->getByItem($id)) . "\n";
echo "Route lines: " . count((new InvItemOperationsRepository())->getByItem($id)) . "\n";
