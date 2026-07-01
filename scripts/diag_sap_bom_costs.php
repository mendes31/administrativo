<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Services\SapReportApiService;

$erp = $argv[1] ?? '60500005';
$safe = str_replace("'", "''", $erp);
$sql = 'SELECT S."ART1_ID" AS "codigo", T2."ItemName" AS "nome", '
    . 'TO_DECIMAL(COALESCE(NULLIF(W."AvgPrice", 0), NULLIF(T2."AvgPrice", 0), 0), 19, 4) AS "component_avg_price" '
    . 'FROM BEAS_STL S '
    . 'INNER JOIN OITM I ON I."ItemCode" = S."ItemCode" '
    . 'LEFT JOIN OITM T2 ON T2."ItemCode" = S."ART1_ID" '
    . 'LEFT JOIN OITW W ON W."ItemCode" = S."ART1_ID" AND W."WhsCode" = T2."DfltWH" '
    . "WHERE S.\"ItemCode\" = '{$safe}' "
    . ' AND UPPER(S."DESCRIPTION") NOT LIKE \'%GERADOR DE LOTE%\'';

$sap = new SapReportApiService();
$resp = $sap->execute($sql);
foreach ($resp['data'] ?? [] as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
