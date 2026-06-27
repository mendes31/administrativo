<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$svc = new App\adms\Models\Services\InventorySapSyncService();
$sap = new App\adms\Models\Services\SapReportApiService();
$ref = new ReflectionClass($svc);
$get = $ref->getMethod('getSapItemsQuery');
$get->setAccessible(true);
$since = date('Y-m-d', strtotime('-90 days'));
$offset = 0;
for ($i = 0; $i < 15; $i++) {
    $sql = $get->invoke($svc, $since, 100, $offset);
    try {
        $n = count($sap->execute($sql)['data'] ?? []);
        echo "batch {$i} off {$offset}: {$n}\n";
        if ($n < 100) break;
        $offset += $n;
        usleep(500000);
    } catch (Throwable $e) {
        echo "batch {$i} ERRO: " . substr($e->getMessage(), 0, 80) . "\n";
        break;
    }
}
