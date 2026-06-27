<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$svc = new App\adms\Models\Services\InventorySapSyncService();
$sap = new App\adms\Models\Services\SapReportApiService();
$ref = new ReflectionClass($svc);
$get = $ref->getMethod('getSapItemsQuery');
$get->setAccessible(true);
$offset = 0;
$total = 0;
for ($i = 0; $i < 100; $i++) {
    $sql = $get->invoke($svc, null, 50, $offset);
    try {
        $n = count($sap->execute($sql)['data'] ?? []);
        $total += $n;
        echo "batch {$i} off {$offset}: +{$n} total {$total}\n";
        if ($n < 50) break;
        $offset += $n;
        usleep(700000);
    } catch (Throwable $e) {
        echo "batch {$i} off {$offset} ERRO: " . substr($e->getMessage(), 0, 70) . "\n";
        // retry same offset with 25
        try {
            usleep(2000000);
            $sql25 = $get->invoke($svc, null, 25, $offset);
            $n = count($sap->execute($sql25)['data'] ?? []);
            echo "  retry25 off {$offset}: +{$n}\n";
            if ($n > 0) { $total += $n; $offset += $n; usleep(700000); continue; }
        } catch (Throwable $e2) {
            echo "  retry25 fail: " . substr($e2->getMessage(), 0, 50) . "\n";
        }
        break;
    }
}
