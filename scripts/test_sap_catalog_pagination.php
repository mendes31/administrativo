<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
use App\adms\Models\Services\SapReportApiService;

$sap = new SapReportApiService();

// Descobrir ItemCode na posição 199 (fim do 1º lote)
$r0 = $sap->execute('SELECT T0."ItemCode" FROM OITM T0 WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode" LIMIT 200 OFFSET 199');
$last = trim((string)(($r0['data'][0]['ItemCode'] ?? '')));
echo "ItemCode pos 199: {$last}\n";

$safe = str_replace("'", "''", $last);
$keyset = 'SELECT T0."ItemCode", T0."ItemName", T0."InvntryUom", T0."validFor", T1."ItmsGrpNam" AS "ItemGroupName" '
    . 'FROM OITM T0 LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" '
    . "WHERE T0.\"frozenFor\" = 'N' AND T0.\"ItemCode\" > '{$safe}' "
    . 'ORDER BY T0."ItemCode" LIMIT 200';

try {
    echo 'keyset OK: ' . count($sap->execute($keyset)['data'] ?? []) . "\n";
} catch (Throwable $e) {
    echo 'keyset ERRO: ' . $e->getMessage() . "\n";
}

// Retry offset 200 com LIMIT 100
foreach ([100, 50] as $lim) {
    $sql = 'SELECT T0."ItemCode" FROM OITM T0 WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode" LIMIT ' . $lim . ' OFFSET 200';
    try {
        echo "offset200 limit{$lim} OK: " . count($sap->execute($sql)['data'] ?? []) . "\n";
    } catch (Throwable $e) {
        echo "offset200 limit{$lim} ERRO: " . substr($e->getMessage(), 0, 60) . "\n";
    }
}
