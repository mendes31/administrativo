<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvItemRouteConsolidatedRepository;

$erp = $argv[1] ?? '40500052';
$itemsRepo = new InvItemsRepository();
$item = $itemsRepo->findByErpCode($erp);
if ($item === null) {
    fwrite(STDERR, "Item {$erp} não encontrado\n");
    exit(1);
}

$itemId = (int)$item['id'];
echo "Item {$erp} id={$itemId}\n";
echo "sap_route_hash: " . ($item['sap_route_hash'] ?? '') . "\n\n";

$opsRepo = new InvItemOperationsRepository();
echo "hasSapSortPosTextColumns: " . ($opsRepo->hasSapSortPosTextColumns() ? 'yes' : 'no') . "\n";
$ops = $opsRepo->getByItem($itemId);
echo "Operações SAP (" . count($ops) . "):\n";
echo "seq;sap_pos_id;sap_sort_id;sap_pos_text;sap_master_pos_id;operation\n";
foreach ($ops as $op) {
    echo implode(';', [
        (string)($op['sequence'] ?? ''),
        (string)($op['sap_pos_id'] ?? ''),
        (string)($op['sap_sort_id'] ?? ''),
        (string)($op['sap_pos_text'] ?? ''),
        (string)($op['sap_master_pos_id'] ?? ''),
        '"' . str_replace('"', "'", (string)($op['operation_name'] ?? '')) . '"',
    ]) . "\n";
}

$consRepo = new InvItemRouteConsolidatedRepository();
echo "\nhasSapGroupPosTextColumn: " . ($consRepo->hasSapGroupPosTextColumn() ? 'yes' : 'no') . "\n";
$cons = $consRepo->getByItem($itemId);
echo "Rota consolidada (" . count($cons) . "):\n";
echo "seq;sap_group_pos_id;sap_group_pos_text;operation\n";
foreach ($cons as $line) {
    echo implode(';', [
        (string)($line['sequence'] ?? ''),
        (string)($line['sap_group_pos_id'] ?? ''),
        (string)($line['sap_group_pos_text'] ?? ''),
        '"' . str_replace('"', "'", (string)($line['operation_name'] ?? '')) . '"',
    ]) . "\n";
}
