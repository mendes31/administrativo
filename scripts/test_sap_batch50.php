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
$total = 0;
for ($i = 0; $i < 30; $i++) {
    $sql = $get->invoke($svc, $since, 50, $offset);
    try {
        $n = count($sap->execute($sql)['data'] ?? []);
        $total += $n;
        echo "batch {$i} off {$offset}: {$n} total {$total}\n";
        if ($n < 50) break;
        $offset += $n;
        usleep(500000);
    } catch (Throwable $e) {
        echo "batch {$i} ERRO: " . substr($e->getMessage(), 0, 60) . "\n";
        break;
    }
}
