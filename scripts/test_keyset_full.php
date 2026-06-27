<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();

$lastCode = '';
$total = 0;
for ($i = 0; $i < 100; $i++) {
    $after = $lastCode !== '' ? " AND T0.\"ItemCode\" > '" . str_replace("'", "''", $lastCode) . "'" : '';
    $sql = 'SELECT T0."ItemCode", T0."ItemName", T0."InvntryUom", T0."validFor", T1."ItmsGrpNam" AS "ItemGroupName", '
        . 'TO_DECIMAL(COALESCE(W."AvgPrice", T0."AvgPrice"), 19, 4) AS "AvgPrice" '
        . 'FROM OITM T0 LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" '
        . 'LEFT JOIN OITW W ON W."ItemCode" = T0."ItemCode" AND W."WhsCode" = CASE '
        . "WHEN T0.\"DfltWH\" = 'TJQP' THEN 'TJQR' WHEN T0.\"DfltWH\" = 'APQP' THEN 'APQR' ELSE T0.\"DfltWH\" END "
        . "WHERE T0.\"frozenFor\" = 'N'{$after} ORDER BY T0.\"ItemCode\" LIMIT 50";
    try {
        $rows = $sap->execute($sql)['data'] ?? [];
        $n = count($rows);
        if ($n === 0) break;
        $total += $n;
        $lastCode = (string)($rows[$n - 1]['ItemCode'] ?? $lastCode);
        if ($i % 10 === 0 || $n < 50) echo "batch {$i}: total {$total} last {$lastCode}\n";
        if ($n < 50) break;
        usleep(400000);
    } catch (Throwable $e) {
        echo "batch {$i} ERRO at {$lastCode}: " . substr($e->getMessage(), 0, 60) . "\n";
        break;
    }
}
echo "DONE {$total}\n";
