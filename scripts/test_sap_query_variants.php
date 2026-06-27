<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();

$queries = [
    'oitm_only' => 'SELECT T0."ItemCode", T0."ItemName", T0."InvntryUom", T0."AvgPrice", T0."validFor", T1."ItmsGrpNam" AS "ItemGroupName" FROM OITM T0 LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" WHERE T0."frozenFor" = \'N\'',
    'oitw_no_oinm' => 'SELECT T0."ItemCode", T0."ItemName", T0."InvntryUom", TO_DECIMAL(COALESCE(W."AvgPrice", T0."AvgPrice"), 19, 4) AS "AvgPrice", T0."validFor", T1."ItmsGrpNam" AS "ItemGroupName" FROM OITM T0 LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" LEFT JOIN OITW W ON W."ItemCode" = T0."ItemCode" AND W."WhsCode" = CASE WHEN T0."DfltWH" = \'TJQP\' THEN \'TJQR\' WHEN T0."DfltWH" = \'APQP\' THEN \'APQR\' ELSE T0."DfltWH" END WHERE T0."frozenFor" = \'N\'',
];

foreach ($queries as $label => $sql) {
    $t = microtime(true);
    try {
        echo "{$label} OK: " . count($sap->execute($sql)['data'] ?? []) . ' em ' . round(microtime(true)-$t,1) . "s\n";
    } catch (Throwable $e) {
        echo "{$label} ERRO: " . substr($e->getMessage(),0,80) . "\n";
    }
}
