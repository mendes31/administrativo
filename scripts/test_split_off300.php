<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$catalog = 'SELECT T0."ItemCode", T0."ItemName", T0."InvntryUom", T0."AvgPrice", T0."validFor", T1."ItmsGrpNam" AS "ItemGroupName" '
    . 'FROM OITM T0 LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" '
    . "WHERE T0.\"frozenFor\" = 'N' ORDER BY T0.\"ItemCode\" LIMIT 50 OFFSET ";
$cost = 'SELECT T0."ItemCode", TO_DECIMAL(COALESCE(W."AvgPrice", T0."AvgPrice"), 19, 4) AS "AvgPrice" '
    . 'FROM OITM T0 LEFT JOIN OITW W ON W."ItemCode" = T0."ItemCode" AND W."WhsCode" = CASE '
    . "WHEN T0.\"DfltWH\" = 'TJQP' THEN 'TJQR' WHEN T0.\"DfltWH\" = 'APQP' THEN 'APQR' ELSE T0.\"DfltWH\" END "
    . "WHERE T0.\"frozenFor\" = 'N' ORDER BY T0.\"ItemCode\" LIMIT 50 OFFSET ";
$offset = 300;
foreach (['catalog' => $catalog, 'cost' => $cost] as $label => $base) {
    try {
        echo "$label off 300: " . count($sap->execute($base . $offset)['data'] ?? []) . "\n";
    } catch (Throwable $e) {
        echo "$label off 300 ERRO\n";
    }
}
