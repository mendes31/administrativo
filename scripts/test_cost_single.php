<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$r = $sap->execute('SELECT T0."ItemCode" FROM OITM T0 WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode" LIMIT 3')['data'] ?? [];
print_r($r);
$c = (string)($r[0]['ItemCode'] ?? '');
$sql = "SELECT T0.\"ItemCode\" FROM OITM T0 WHERE T0.\"ItemCode\" = '{$c}'";
echo count($sap->execute($sql)['data'] ?? []) . " for single\n";
