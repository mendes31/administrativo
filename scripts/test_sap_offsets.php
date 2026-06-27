<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
use App\adms\Models\Services\SapReportApiService;

$sap = new SapReportApiService();
foreach ([0, 200, 4000, 4200, 4400] as $off) {
    $sql = 'SELECT T0."ItemCode" FROM OITM T0 LEFT JOIN OITW W ON W."ItemCode" = T0."ItemCode" '
        . 'AND W."WhsCode" = CASE WHEN T0."DfltWH" = \'TJQP\' THEN \'TJQR\' WHEN T0."DfltWH" = \'APQP\' THEN \'APQR\' ELSE T0."DfltWH" END '
        . 'WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode" LIMIT 200 OFFSET ' . $off;
    try {
        $r = $sap->execute($sql);
        echo "OFFSET {$off} OK: " . count($r['data'] ?? []) . "\n";
    } catch (Throwable $e) {
        echo "OFFSET {$off} ERRO: " . substr($e->getMessage(), 0, 80) . "\n";
    }
}
