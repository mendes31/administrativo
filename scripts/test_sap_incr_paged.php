<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$since = date('Y-m-d', strtotime('-90 days'));
$base = 'SELECT T0."ItemCode" FROM OITM T0 LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" '
    . 'LEFT JOIN OITW W ON W."ItemCode" = T0."ItemCode" AND W."WhsCode" = CASE '
    . "WHEN T0.\"DfltWH\" = 'TJQP' THEN 'TJQR' WHEN T0.\"DfltWH\" = 'APQP' THEN 'APQR' ELSE T0.\"DfltWH\" END "
    . "WHERE T0.\"frozenFor\" = 'N' AND T0.\"UpdateDate\" >= '{$since}' ORDER BY T0.\"ItemCode\" LIMIT 100 OFFSET ";
$offset = 0;
for ($i = 0; $i < 15; $i++) {
    try {
        $n = count($sap->execute($base . $offset)['data'] ?? []);
        echo "batch {$i} off {$offset}: {$n}\n";
        if ($n < 100) break;
        $offset += $n;
        usleep(500000);
    } catch (Throwable $e) {
        echo "batch {$i} ERRO: " . $e->getMessage() . "\n";
        break;
    }
}
