<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$svc = new App\adms\Models\Services\InventorySapSyncService();
$ref = new ReflectionClass($svc);
$get = $ref->getMethod('getSapItemsQuery');
$get->setAccessible(true);
$sql = $get->invoke($svc, '2026-06-27', 50, 0);
echo $sql . "\n\n";
try {
    $n = count((new App\adms\Models\Services\SapReportApiService())->execute($sql)['data'] ?? []);
    echo "OK: {$n} rows\n";
} catch (Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
