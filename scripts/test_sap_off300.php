<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$s = new App\adms\Models\Services\SapReportApiService();
$sql = 'SELECT T0."ItemCode" FROM OITM T0 LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" '
    . "WHERE T0.\"frozenFor\" = 'N' ORDER BY T0.\"ItemCode\" LIMIT 100 OFFSET 300";
try {
    echo 'alone OK: ' . count($s->execute($sql)['data'] ?? []) . "\n";
} catch (Throwable $e) {
    echo 'alone ERRO: ' . $e->getMessage() . "\n";
}
