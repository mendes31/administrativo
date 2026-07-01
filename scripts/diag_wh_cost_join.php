<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Services\SapReportApiService;

$sap = new SapReportApiService();

foreach (['60500005', '61000095', '41000052'] as $p) {
    $safe = str_replace("'", "''", $p);
    $r = $sap->execute('SELECT "ItemCode","DfltWH","AvgPrice" FROM OITM WHERE "ItemCode"=\'' . $safe . '\'');
    echo $p . ': ' . json_encode($r['data'][0] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

$sql = 'SELECT S."ART1_ID" AS "codigo", I."DfltWH" AS "parent_wh", T2."DfltWH" AS "comp_wh", '
    . 'W."AvgPrice" AS "wrong_parent_wh_cost", W2."AvgPrice" AS "correct_comp_wh_cost", '
    . 'T2."AvgPrice" AS "oitm_avg" '
    . 'FROM BEAS_STL S '
    . 'INNER JOIN OITM I ON I."ItemCode" = S."ItemCode" '
    . 'LEFT JOIN OITM T2 ON T2."ItemCode" = S."ART1_ID" '
    . 'LEFT JOIN OITW W ON W."ItemCode" = S."ART1_ID" AND W."WhsCode" = I."DfltWH" '
    . 'LEFT JOIN OITW W2 ON W2."ItemCode" = S."ART1_ID" AND W2."WhsCode" = T2."DfltWH" '
    . "WHERE S.\"ItemCode\" = '60500005' "
    . ' AND UPPER(S."DESCRIPTION") NOT LIKE \'%GERADOR DE LOTE%\'';

echo PHP_EOL . '=== BOM 60500005 custos (depósito errado vs correto) ===' . PHP_EOL;
foreach ($sap->execute($sql)['data'] ?? [] as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
