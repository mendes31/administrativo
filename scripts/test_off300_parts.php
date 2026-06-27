<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$tests = [
    'oitm_only' => 'SELECT T0."ItemCode", T0."ItemName", T0."InvntryUom", T0."AvgPrice", T0."validFor", T0."ItmsGrpCod" FROM OITM T0 WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode" LIMIT 50 OFFSET 300',
    'oitb_join' => 'SELECT T0."ItemCode", T1."ItmsGrpNam" FROM OITM T0 LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode" LIMIT 50 OFFSET 300',
];
foreach ($tests as $k => $sql) {
    try {
        echo "$k: " . count($sap->execute($sql)['data'] ?? []) . "\n";
    } catch (Throwable $e) {
        echo "$k ERRO\n";
    }
}
