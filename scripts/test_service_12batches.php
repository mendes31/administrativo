<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$svc = new App\adms\Models\Services\InventorySapSyncService();
$sap = new App\adms\Models\Services\SapReportApiService();
$ref = new ReflectionClass($svc);
$batch = $ref->getMethod('fetchOitmKeysetBatchWithCost');
$batch->setAccessible(true);
$last = '';
for ($i = 0; $i < 12; $i++) {
    try {
        $rows = $batch->invoke($svc, $sap, $last, 50);
        $n = count($rows);
        echo "batch {$i}: {$n} last ";
        if ($n === 0) break;
        $last = trim((string)($rows[$n-1]['ItemCode'] ?? $last));
        echo $last . "\n";
        usleep(1500000);
    } catch (Throwable $e) {
        echo "batch {$i} ERRO at last {$last}\n";
        break;
    }
}
