<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InventorySapSyncService;

$erp = $argv[1] ?? '40500052';
$item = (new InvItemsRepository())->findByErpCode($erp);
if ($item === null) {
    fwrite(STDERR, "Item not found\n");
    exit(1);
}

$result = (new InventorySapSyncService())->syncItemStructureById((int)$item['id']);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
