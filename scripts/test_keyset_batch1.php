<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
sleep(3);
$sql = 'SELECT T0."ItemCode" FROM OITM T0 WHERE T0."frozenFor" = \'N\' AND T0."ItemCode" > \'1000051\' ORDER BY T0."ItemCode" LIMIT 50';
try {
    echo 'batch1 oitm: ' . count($sap->execute($sql)['data'] ?? []) . "\n";
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . "\n";
}
