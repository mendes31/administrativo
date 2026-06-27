<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
use App\adms\Models\Services\InventorySapSyncService;
use App\adms\Models\Services\SapReportApiService;

$sap = new SapReportApiService();
$svc = new InventorySapSyncService();
$ref = new ReflectionClass($svc);
$fetch = $ref->getMethod('fetchAllSapItemRows');
$fetch->setAccessible(true);
$catalog = $ref->getMethod('getSapItemsCatalogQueryPage');
$catalog->setAccessible(true);
$cost = $ref->getMethod('getSapItemsCostQueryPage');
$cost->setAccessible(true);
$retry = $ref->getMethod('executeSapQueryWithRetryAndLimit');
$retry->setAccessible(true);

$batchSize = $ref->getMethod('resolveSapCatalogBatchSize');
$batchSize->setAccessible(true);
$bs = (int) $batchSize->invoke($svc);
echo "batchSize={$bs}\n";

$offset = 0;
$total = 0;
for ($b = 0; $b < 500; $b++) {
    try {
        [$catResp, $lim] = $retry->invoke($svc, $sap, $catalog->invoke($svc, $offset, $bs), $bs);
        $catRows = ($ref->getMethod('extractSapRows'))->invoke($svc, $catResp);
        if ($catRows === []) {
            echo "batch {$b} offset {$offset}: fim\n";
            break;
        }
        [$costResp] = $retry->invoke($svc, $sap, $cost->invoke($svc, $offset, $lim), $lim);
        $n = count($catRows);
        $total += $n;
        echo "batch {$b} offset {$offset} lim {$lim}: +{$n} (total {$total})\n";
        if ($n < $lim) {
            break;
        }
        $offset += $n;
    } catch (Throwable $e) {
        echo "batch {$b} offset {$offset} ERRO: " . $e->getMessage() . "\n";
        break;
    }
}
