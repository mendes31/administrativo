<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
use App\adms\Models\Services\SapReportApiService;

$sap = new SapReportApiService();
$sql = 'SELECT T0."ItemCode", T0."ItemName", TO_DECIMAL(COALESCE(W."AvgPrice", T0."AvgPrice"), 19, 4) AS "AvgPrice" '
    . 'FROM OITM T0 LEFT JOIN OITW W ON W."ItemCode" = T0."ItemCode" '
    . 'AND W."WhsCode" = CASE WHEN T0."DfltWH" = \'TJQP\' THEN \'TJQR\' WHEN T0."DfltWH" = \'APQP\' THEN \'APQR\' ELSE T0."DfltWH" END '
    . 'WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode" LIMIT 200 OFFSET 200';
try {
    $r = $sap->execute($sql);
    echo 'OFFSET 200 OK: ' . count($r['data'] ?? []) . "\n";
} catch (Throwable $e) {
    echo 'OFFSET ERRO: ' . $e->getMessage() . "\n";
}
