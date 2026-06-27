<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
use App\adms\Models\Services\InventorySapSyncService;
use App\adms\Models\Services\SapReportApiService;

$sap = new SapReportApiService();
$svc = new InventorySapSyncService();
$ref = new ReflectionClass($svc);
$m = $ref->getMethod('fetchAllSapItemRows');
$m->setAccessible(true);
$start = microtime(true);
try {
    $rows = $m->invoke($svc, $sap);
    echo 'OK: ' . count($rows) . ' itens em ' . round(microtime(true) - $start, 1) . "s\n";
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . "\n";
}
