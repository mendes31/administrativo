<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

use App\adms\Models\Services\InventorySapSyncService;
use App\adms\Models\Services\SapReportApiService;

$sap = new SapReportApiService();
$svc = new InventorySapSyncService();
$ref = new ReflectionClass($svc);
$getQuery = $ref->getMethod('getSapItemsQuery');
$getQuery->setAccessible(true);

foreach ([null, date('Y-m-d', strtotime('-7 days'))] as $since) {
    $label = $since === null ? 'full' : "since_{$since}";
    $sql = $getQuery->invoke($svc, $since);
    echo "=== {$label} (len " . strlen($sql) . ") ===\n";
    $t = microtime(true);
    try {
        $r = $sap->execute($sql);
        $n = count($r['data'] ?? []);
        echo "OK: {$n} rows em " . round(microtime(true) - $t, 1) . "s\n";
    } catch (Throwable $e) {
        echo "ERRO em " . round(microtime(true) - $t, 1) . "s: " . $e->getMessage() . "\n";
    }
}
