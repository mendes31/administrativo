<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$sql = 'SELECT T0."ItemCode" FROM OITM T0 WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode" LIMIT 50 OFFSET 300';
try {
    echo 'alone OK: ' . count($sap->execute($sql)['data'] ?? []) . "\n";
} catch (Throwable $e) {
    echo 'alone ERRO: ' . $e->getMessage() . "\n";
}
