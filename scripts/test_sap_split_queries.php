<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
use App\adms\Models\Services\InventorySapSyncService;
use App\adms\Models\Services\SapReportApiService;

$sap = new SapReportApiService();
$svc = new InventorySapSyncService();
$ref = new ReflectionClass($svc);
$catalog = $ref->getMethod('getSapItemsCatalogQueryPage');
$catalog->setAccessible(true);
$cost = $ref->getMethod('getSapItemsCostQueryPage');
$cost->setAccessible(true);

foreach ([0, 200, 400, 600] as $off) {
    foreach (['catalog' => $catalog, 'cost' => $cost] as $label => $m) {
        $sql = $m->invoke($svc, $off, 200);
        try {
            $n = count($sap->execute($sql)['data'] ?? []);
            echo "{$label} OFFSET {$off} OK: {$n}\n";
        } catch (Throwable $e) {
            echo "{$label} OFFSET {$off} ERRO: " . substr($e->getMessage(), 0, 70) . "\n";
        }
    }
}
