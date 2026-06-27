<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$since = date('Y-m-d', strtotime('-90 days'));
$sql = 'SELECT COUNT(*) AS "C" FROM OITM T0 WHERE T0."frozenFor" = \'N\' AND T0."UpdateDate" >= \'' . $since . '\'';
try {
    echo "count since {$since}: " . json_encode($sap->execute($sql)['data'][0] ?? []) . "\n";
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . "\n";
}
