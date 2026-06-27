<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
use App\adms\Models\Services\InventorySapSyncService;
use App\adms\Models\Services\SapReportApiService;

$sap = new SapReportApiService();
$svc = new InventorySapSyncService();
$ref = new ReflectionClass($svc);
$m = $ref->getMethod('getSapItemsQueryPage');
$m->setAccessible(true);
foreach ([0, 200, 400] as $off) {
    $sql = $m->invoke($svc, $off, 200);
    try {
        $r = $sap->execute($sql);
        echo "full page OFFSET {$off} OK: " . count($r['data'] ?? []) . "\n";
    } catch (Throwable $e) {
        echo "full page OFFSET {$off} ERRO: " . $e->getMessage() . "\n";
    }
}
