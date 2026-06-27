<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
foreach ([
    "SELECT T0.\"ItemCode\" FROM OITM T0 WHERE T0.\"ItemCode\" = '1000001'",
    "SELECT T0.\"ItemCode\" FROM OITM T0 WHERE T0.\"ItemCode\" = 1000001",
    "SELECT T0.\"ItemCode\" FROM OITM T0 WHERE CAST(T0.\"ItemCode\" AS NVARCHAR(50)) = '1000001'",
] as $i => $sql) {
    try {
        echo "$i: " . count($sap->execute($sql)['data'] ?? []) . "\n";
    } catch (Throwable $e) {
        echo "$i ERRO\n";
    }
}
