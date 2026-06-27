<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
use App\adms\Models\Services\SapReportApiService;
use App\adms\Models\Services\InventorySapSyncService;

$sap = new SapReportApiService();
$svc = new InventorySapSyncService();
$ref = new ReflectionClass($svc);
$m = $ref->getMethod('getSapItemsQueryPage');
$m->setAccessible(true);

$costJoins = $ref->getMethod('sapOitwJoinSql');
$costJoins->setAccessible(true);
$join = $costJoins->invoke($svc, 'T0."ItemCode"', 'T0."DfltWH"');

$tests = [
    'full_via_method' => fn () => $m->invoke($svc, 200, 200),
    'both_joins_min' => 'SELECT T0."ItemCode", T0."ItemName" FROM OITM T0 '
        . 'LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" '
        . $join . ' '
        . "WHERE T0.\"frozenFor\" = 'N' ORDER BY T0.\"ItemCode\" LIMIT 200 OFFSET 200",
    'both_joins_all_cols' => 'SELECT T0."ItemCode", T0."ItemName", T0."InvntryUom", '
        . 'TO_DECIMAL(COALESCE(W."AvgPrice", T0."AvgPrice"), 19, 4) AS "AvgPrice", '
        . 'T0."validFor", T1."ItmsGrpNam" AS "ItemGroupName" '
        . 'FROM OITM T0 '
        . 'LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" '
        . $join . ' '
        . "WHERE T0.\"frozenFor\" = 'N' ORDER BY T0.\"ItemCode\" LIMIT 200 OFFSET 200",
];

foreach ($tests as $label => $sqlOrFn) {
    $sql = is_callable($sqlOrFn) ? $sqlOrFn() : $sqlOrFn;
    try {
        $n = count($sap->execute($sql)['data'] ?? []);
        echo "$label OK: $n\n";
    } catch (Throwable $e) {
        echo "$label ERRO: " . substr($e->getMessage(), 0, 80) . "\n";
    }
}
