<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
use App\adms\Models\Services\InventorySapSyncService;
use App\adms\Models\Services\SapReportApiService;

$sap = new SapReportApiService();
$svc = new InventorySapSyncService();
$ref = new ReflectionClass($svc);
$m = $ref->getMethod('getSapItemsQueryBatch');
$m->setAccessible(true);
$sql = $m->invoke($svc, '10500079', 200);
echo $sql . "\n\n";
try {
    $r = $sap->execute($sql);
    echo 'OK: ' . count($r['data'] ?? []) . "\n";
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . "\n";
}
