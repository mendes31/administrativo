<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$svc = new App\adms\Models\Services\InventorySapSyncService();
$r = $svc->syncItemsAndCosts(false);
echo ($r['success'] ? 'OK' : 'FAIL') . ': ' . ($r['message'] ?? '') . "\n";
