<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$last = '';
for ($i = 0; $i < 4; $i++) {
    $after = $last !== '' ? " AND T0.\"ItemCode\" > '" . str_replace("'", "''", $last) . "'" : '';
    $sql = 'SELECT T0."ItemCode", T0."ItemName", T0."InvntryUom", T0."validFor", T0."ItmsGrpCod", '
        . 'TO_DECIMAL(COALESCE(W."AvgPrice", T0."AvgPrice"), 19, 4) AS "AvgPrice" '
        . 'FROM OITM T0 LEFT JOIN OITW W ON W."ItemCode" = T0."ItemCode" AND W."WhsCode" = CASE '
        . "WHEN T0.\"DfltWH\" = 'TJQP' THEN 'TJQR' WHEN T0.\"DfltWH\" = 'APQP' THEN 'APQR' ELSE T0.\"DfltWH\" END "
        . "WHERE T0.\"frozenFor\" = 'N'{$after} ORDER BY T0.\"ItemCode\" LIMIT 50";
    try {
        $rows = $sap->execute($sql)['data'] ?? [];
        $n = count($rows);
        $last = (string)($rows[$n-1]['ItemCode'] ?? $last);
        echo "batch {$i}: {$n}\n";
        usleep(1500000);
    } catch (Throwable $e) {
        echo "batch {$i} ERRO\n";
        break;
    }
}
