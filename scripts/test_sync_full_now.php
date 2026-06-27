<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$since = '2026-06-27';
$c = $sap->execute('SELECT COUNT(*) AS "C" FROM OITM T0 WHERE T0."frozenFor" = \'N\' AND T0."UpdateDate" >= \'' . $since . '\'')['data'][0]['C'] ?? '?';
echo "since {$since}: {$c}\n";

$r = (new App\adms\Models\Services\InventorySapSyncService())->syncItemsAndCosts(true);
echo ($r['success'] ? 'FULL OK' : 'FULL FAIL') . ': ' . ($r['message'] ?? '') . "\n";
