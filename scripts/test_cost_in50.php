<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$rows = $sap->execute('SELECT T0."ItemCode", T0."DfltWH" FROM OITM T0 WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode" LIMIT 50')['data'] ?? [];
$codes = array_map(fn($r) => str_replace("'","''",(string)$r['ItemCode']), $rows);
$in = implode("','", $codes);
$sql = 'SELECT T0."ItemCode", TO_DECIMAL(COALESCE(W."AvgPrice", T0."AvgPrice"), 19, 4) AS "AvgPrice" FROM OITM T0 '
    . "LEFT JOIN OITW W ON W.\"ItemCode\" = T0.\"ItemCode\" AND W.\"WhsCode\" = CASE WHEN T0.\"DfltWH\" = 'TJQP' THEN 'TJQR' WHEN T0.\"DfltWH\" = 'APQP' THEN 'APQR' ELSE T0.\"DfltWH\" END "
    . "WHERE T0.\"ItemCode\" IN ('{$in}')";
try {
    echo 'cost IN 50: ' . count($sap->execute($sql)['data'] ?? []) . "\n";
} catch (Throwable $e) {
    echo 'cost ERRO: ' . $e->getMessage() . "\n";
}
