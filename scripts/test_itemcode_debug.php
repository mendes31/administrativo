<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$sql = "SELECT T0.\"ItemCode\", T0.\"frozenFor\" FROM OITM T0 WHERE T0.\"frozenFor\" = 'N' ORDER BY T0.\"ItemCode\" LIMIT 1";
$r = $sap->execute($sql)['data'][0] ?? [];
var_export($r);
$c = trim((string)($r['ItemCode'] ?? ''));
echo "\nlen=" . strlen($c) . " hex=" . bin2hex($c) . "\n";
$sql2 = "SELECT COUNT(*) AS C FROM OITM T0 WHERE T0.\"frozenFor\" = 'N' AND T0.\"ItemCode\" = '{$c}'";
echo 'match: ' . json_encode($sap->execute($sql2)['data'][0] ?? []) . "\n";
