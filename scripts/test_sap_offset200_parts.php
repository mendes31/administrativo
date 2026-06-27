<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
use App\adms\Models\Services\SapReportApiService;
$s = new SapReportApiService();
$tests = [
    'oitb_only' => 'SELECT T0."ItemCode", T1."ItmsGrpNam" AS "ItemGroupName" FROM OITM T0 LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode" LIMIT 200 OFFSET 200',
    'oitw_only' => 'SELECT T0."ItemCode", TO_DECIMAL(COALESCE(W."AvgPrice", T0."AvgPrice"), 19, 4) AS "AvgPrice" FROM OITM T0 LEFT JOIN OITW W ON W."ItemCode" = T0."ItemCode" AND W."WhsCode" = CASE WHEN T0."DfltWH" = \'TJQP\' THEN \'TJQR\' WHEN T0."DfltWH" = \'APQP\' THEN \'APQR\' ELSE T0."DfltWH" END WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode" LIMIT 200 OFFSET 200',
    'no_join' => 'SELECT T0."ItemCode", T0."ItemName", T0."AvgPrice" FROM OITM T0 WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode" LIMIT 200 OFFSET 200',
];
foreach ($tests as $label => $sql) {
    try {
        echo "$label OK: " . count($s->execute($sql)['data'] ?? []) . "\n";
    } catch (Throwable $e) {
        echo "$label ERRO: " . substr($e->getMessage(), 0, 60) . "\n";
    }
}
