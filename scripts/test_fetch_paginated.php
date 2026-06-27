<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$svc = new App\adms\Models\Services\InventorySapSyncService();
$sap = new App\adms\Models\Services\SapReportApiService();
$ref = new ReflectionClass($svc);
$m = $ref->getMethod('fetchSapItemsPaginated');
$m->setAccessible(true);
$since = date('Y-m-d', strtotime('-14 days'));
try {
    $rows = $m->invoke($svc, $sap, $since);
    echo 'OK: ' . count($rows) . " rows\n";
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . "\n";
}
