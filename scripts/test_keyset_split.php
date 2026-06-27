<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();

$groups = [];
foreach ($sap->execute('SELECT "ItmsGrpCod", "ItmsGrpNam" FROM OITB')['data'] ?? [] as $g) {
    $groups[$g['ItmsGrpCod'] ?? $g['ITMSGRPCOD'] ?? ''] = $g['ItmsGrpNam'] ?? $g['ITMSGRPNAM'] ?? 'Geral';
}
echo 'groups: ' . count($groups) . "\n";

$lastCode = '';
$total = 0;
for ($i = 0; $i < 100; $i++) {
    $after = $lastCode !== '' ? " AND T0.\"ItemCode\" > '" . str_replace("'", "''", $lastCode) . "'" : '';
    $sqlOitm = 'SELECT T0."ItemCode", T0."ItemName", T0."InvntryUom", T0."validFor", T0."ItmsGrpCod", T0."AvgPrice", T0."DfltWH" '
        . "FROM OITM T0 WHERE T0.\"frozenFor\" = 'N'{$after} ORDER BY T0.\"ItemCode\" LIMIT 50";
    try {
        $rows = $sap->execute($sqlOitm)['data'] ?? [];
        $n = count($rows);
        if ($n === 0) break;

        $codes = array_map(fn($r) => str_replace("'", "''", (string)($r['ItemCode'] ?? '')), $rows);
        $codes = array_filter($codes);
        $inList = implode("','", $codes);
        $costSql = 'SELECT T0."ItemCode", TO_DECIMAL(COALESCE(W."AvgPrice", T0."AvgPrice"), 19, 4) AS "AvgPrice" '
            . 'FROM OITM T0 LEFT JOIN OITW W ON W."ItemCode" = T0."ItemCode" AND W."WhsCode" = CASE '
            . "WHEN T0.\"DfltWH\" = 'TJQP' THEN 'TJQR' WHEN T0.\"DfltWH\" = 'APQP' THEN 'APQR' ELSE T0.\"DfltWH\" END "
            . "WHERE T0.\"ItemCode\" IN ('{$inList}')";
        $costMap = [];
        foreach ($sap->execute($costSql)['data'] ?? [] as $c) {
            $costMap[(string)($c['ItemCode'] ?? '')] = $c['AvgPrice'] ?? null;
        }

        $total += $n;
        $lastCode = (string)($rows[$n - 1]['ItemCode'] ?? $lastCode);
        if ($i % 15 === 0 || $n < 50) echo "batch {$i}: total {$total} last {$lastCode}\n";
        if ($n < 50) break;
        usleep(400000);
    } catch (Throwable $e) {
        echo "batch {$i} ERRO: " . substr($e->getMessage(), 0, 60) . "\n";
        break;
    }
}
echo "DONE {$total}\n";
