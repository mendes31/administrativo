<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$svc = new App\adms\Models\Services\InventorySapSyncService();
$sap = new App\adms\Models\Services\SapReportApiService();
$ref = new ReflectionClass($svc);
$m = $ref->getMethod('fetchSapItemsFullCatalog');
$m->setAccessible(true);
$t = microtime(true);
try {
    $rows = $m->invoke($svc, $sap);
    echo 'OK: ' . count($rows) . ' itens em ' . round(microtime(true) - $t, 0) . "s\n";
} catch (Throwable $e) {
    echo 'ERRO após ' . round(microtime(true) - $t, 0) . 's: ' . $e->getMessage() . "\n";
}
