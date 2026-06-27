<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$tests = [
    'top5' => 'SELECT TOP 5 T0."ItemCode" FROM OITM T0 WHERE T0."frozenFor" = \'N\'',
    'limit5' => 'SELECT T0."ItemCode" FROM OITM T0 WHERE T0."frozenFor" = \'N\' LIMIT 5',
    'count' => 'SELECT COUNT(*) AS "C" FROM OITM T0 WHERE T0."frozenFor" = \'N\'',
    'dummy' => 'SELECT 1 AS "X" FROM DUMMY',
];
foreach ($tests as $k => $sql) {
    try {
        $d = $sap->execute($sql)['data'] ?? [];
        echo "$k OK: " . json_encode($d[0] ?? $d) . "\n";
    } catch (Throwable $e) {
        echo "$k ERRO: " . $e->getMessage() . "\n";
    }
}
