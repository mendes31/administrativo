<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
foreach ([7, 14, 30] as $days) {
    $since = date('Y-m-d', strtotime("-{$days} days"));
    $c = $sap->execute('SELECT COUNT(*) AS "C" FROM OITM T0 WHERE T0."frozenFor" = \'N\' AND T0."UpdateDate" >= \'' . $since . '\'')['data'][0]['C'] ?? '?';
    echo "{$days}d since {$since}: {$c}\n";
}
