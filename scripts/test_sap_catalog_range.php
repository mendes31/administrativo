<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
use App\adms\Models\Services\SapReportApiService;
$sap = new SapReportApiService();
$sql = 'SELECT T0."ItemCode", T0."ItemName", T1."ItmsGrpNam" AS "ItemGroupName" FROM OITM T0 '
    . 'LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" '
    . "WHERE T0.\"frozenFor\" = 'N' ORDER BY T0.\"ItemCode\" LIMIT 100 OFFSET ";
for ($off = 200; $off <= 350; $off += 50) {
    try {
        $n = count($sap->execute($sql . $off)['data'] ?? []);
        echo "catalog off {$off} lim100 OK: {$n}\n";
    } catch (Throwable $e) {
        echo "catalog off {$off} lim100 ERRO\n";
    }
}
