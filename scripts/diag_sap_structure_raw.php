<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Services\SapReportApiService;

$erp = $argv[1] ?? '60500005';

// Replicate query building via reflection or duplicate minimal queries
$safeCode = str_replace("'", "''", trim($erp));
$versionSql = ' AND V."Version" = COALESCE(NULLIF(TRIM(I."U_beas_ver"), \'\'), ('
    . 'SELECT MAX(VX."Version") FROM BEAS_ITEM_VERSION VX WHERE VX."ItemCode" = I."ItemCode"'
    . ')) ';

$materialsSql = 'SELECT '
    . 'S."POS_ID" AS "pos_id", '
    . 'S."ART1_ID" AS "codigo", '
    . 'S."DESCRIPTION" AS "descricao", '
    . 'S."INPUT_QTY" AS "quantidade", '
    . 'S."INPUT_UNIT" AS "unidade_medida" '
    . 'FROM BEAS_STL S '
    . 'INNER JOIN BEAS_ITEM_VERSION V ON S."ItemCode" = V."StlItemCode" '
    . 'INNER JOIN OITM I ON V."ItemCode" = I."ItemCode" '
    . $versionSql
    . "AND I.\"ItemCode\" = '{$safeCode}' "
    . 'LEFT JOIN OITM T2 ON T2."ItemCode" = S."ART1_ID" '
    . ' WHERE UPPER(S."DESCRIPTION") NOT LIKE \'%GERADOR DE LOTE%\'';

$routeSql = 'SELECT '
    . 'A."POS_ID" AS "pos_id", '
    . 'A."AG_ID" AS "codigo", '
    . 'A."BEZ" AS "descricao", '
    . 'A."APLATZ_ID" AS "recurso" '
    . 'FROM BEAS_APL A '
    . 'INNER JOIN BEAS_ITEM_VERSION V ON A."ItemCode" = V."RoutingId" '
    . 'INNER JOIN OITM I ON V."ItemCode" = I."ItemCode" '
    . $versionSql
    . "AND I.\"ItemCode\" = '{$safeCode}'";

$sap = new SapReportApiService();

echo "=== SAP MATERIAIS {$erp} ===\n";
try {
    $resp = $sap->execute($materialsSql);
    $mats = is_array($resp['data'] ?? null) ? $resp['data'] : [];
    echo 'count=' . count($mats) . " rows_count=" . ($resp['rows_count'] ?? '?') . "\n";
    if ($mats !== []) {
        echo 'keys sample: ' . implode(', ', array_keys($mats[0])) . "\n";
        foreach ($mats as $row) {
            echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . "\n";
}

echo "\n=== SAP ROTA {$erp} ===\n";
try {
    $resp = $sap->execute($routeSql);
    $routes = is_array($resp['data'] ?? null) ? $resp['data'] : [];
    echo 'count=' . count($routes) . "\n";
    foreach ($routes as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    }
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . "\n";
}

// Diagnóstico versão BEAS — consultas alternativas
$queries = [
    'BEAS_ITEM_VERSION' => "SELECT I.\"ItemCode\", I.\"U_beas_ver\", V.\"Version\", V.\"StlItemCode\", V.\"RoutingId\" "
        . "FROM OITM I LEFT JOIN BEAS_ITEM_VERSION V ON V.\"ItemCode\" = I.\"ItemCode\" "
        . "WHERE I.\"ItemCode\" = '{$safeCode}'",
    'BEAS_STL direto' => "SELECT S.\"ItemCode\", S.\"POS_ID\", S.\"ART1_ID\", S.\"DESCRIPTION\", S.\"INPUT_QTY\" "
        . "FROM BEAS_STL S WHERE S.\"ItemCode\" = '{$safeCode}'",
    'BEAS_STL via versão max' => "SELECT S.\"ItemCode\", S.\"POS_ID\", S.\"ART1_ID\", S.\"DESCRIPTION\", S.\"INPUT_QTY\", V.\"Version\" "
        . "FROM BEAS_STL S INNER JOIN BEAS_ITEM_VERSION V ON S.\"ItemCode\" = V.\"StlItemCode\" "
        . "WHERE V.\"ItemCode\" = '{$safeCode}'",
    'BEAS_APL direto' => "SELECT A.\"ItemCode\", A.\"POS_ID\", A.\"AG_ID\", A.\"BEZ\", A.\"APLATZ_ID\" "
        . "FROM BEAS_APL A WHERE A.\"ItemCode\" = '{$safeCode}'",
];

foreach ($queries as $label => $sql) {
    echo "\n=== {$label} {$erp} ===\n";
    try {
        $resp = $sap->execute($sql);
        $rows = is_array($resp['data'] ?? null) ? $resp['data'] : [];
        echo 'count=' . count($rows) . "\n";
        foreach (array_slice($rows, 0, 10) as $row) {
            echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
        }
    } catch (Throwable $e) {
        echo 'ERRO: ' . $e->getMessage() . "\n";
    }
}
