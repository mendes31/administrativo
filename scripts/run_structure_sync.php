<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InventorySapSyncService;

$erp = $argv[1] ?? '40500052';
$structureOnly = in_array('--structure-only', $argv, true);

$item = (new InvItemsRepository())->findByErpCode($erp);
if ($item === null) {
    fwrite(STDERR, "Item not found\n");
    exit(1);
}

$service = new InventorySapSyncService();
if ($structureOnly) {
    $result = $service->syncItemStructureById((int)$item['id']);
} else {
    $result = $service->syncItemUnifiedByErpCode($erp);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
