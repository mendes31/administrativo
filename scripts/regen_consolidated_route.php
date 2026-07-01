<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvItemRouteConsolidatedRepository;
use App\adms\Models\Services\InvRouteConsolidationService;

$erp = $argv[1] ?? '40500052';
$item = (new InvItemsRepository())->findByErpCode($erp);
if ($item === null) {
    fwrite(STDERR, "Item not found\n");
    exit(1);
}

$itemId = (int)$item['id'];
$sapRoute = (new InvItemOperationsRepository())->getByItem($itemId);
$lines = (new InvRouteConsolidationService())->ensureForItem($itemId, true);
echo ($lines['created'] ? 'OK (criada)' : 'OK (já existia ou sem rota)') . ' — ' . $lines['lines'] . " grupos\n";
