<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$base = 'SELECT T0."ItemCode", T0."ItemName", T0."InvntryUom", '
    . 'TO_DECIMAL(COALESCE(W."AvgPrice", T0."AvgPrice"), 19, 4) AS "AvgPrice", '
    . 'T0."validFor", T1."ItmsGrpNam" AS "ItemGroupName" '
    . 'FROM OITM T0 LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" '
    . 'LEFT JOIN OITW W ON W."ItemCode" = T0."ItemCode" AND W."WhsCode" = CASE '
    . "WHEN T0.\"DfltWH\" = 'TJQP' THEN 'TJQR' WHEN T0.\"DfltWH\" = 'APQP' THEN 'APQR' ELSE T0.\"DfltWH\" END "
    . "WHERE T0.\"frozenFor\" = 'N' ORDER BY T0.\"ItemCode\" LIMIT 100 OFFSET ";
$offset = 0;
$total = 0;
for ($i = 0; $i < 50; $i++) {
    try {
        $rows = $sap->execute($base . $offset)['data'] ?? [];
        $n = count($rows);
        if ($n === 0) break;
        $total += $n;
        echo "batch {$i} offset {$offset}: +{$n} total {$total}\n";
        if ($n < 100) break;
        $offset += $n;
        usleep(200000);
    } catch (Throwable $e) {
        echo "batch {$i} offset {$offset} ERRO: " . $e->getMessage() . "\n";
        break;
    }
}
echo "DONE total {$total}\n";
