<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$sql = 'SELECT T0."ItemCode", T0."ItemName" FROM OITM T0 WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode" LIMIT 1';
$r = $sap->execute($sql)['data'][0] ?? [];
$name = str_replace("'", "''", (string)($r['ItemName'] ?? ''));
$sql2 = "SELECT T0.\"ItemCode\" FROM OITM T0 WHERE T0.\"ItemName\" = '{$name}'";
echo json_encode($sap->execute($sql2)['data'] ?? []) . "\n";
