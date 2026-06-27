<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$sap = new App\adms\Models\Services\SapReportApiService();
$last = '';
for ($i = 0; $i < 5; $i++) {
    $after = $last !== '' ? " AND T0.\"ItemCode\" > '" . str_replace("'", "''", $last) . "'" : '';
    $sql = 'SELECT T0."ItemCode" FROM OITM T0 WHERE T0."frozenFor" = \'N\'' . $after . ' ORDER BY T0."ItemCode" LIMIT 50';
    try {
        $rows = $sap->execute($sql)['data'] ?? [];
        $n = count($rows);
        $last = (string)($rows[$n-1]['ItemCode'] ?? $last);
        echo "oitm batch {$i}: {$n} last {$last}\n";
        usleep(1500000);
    } catch (Throwable $e) {
        echo "oitm batch {$i} ERRO\n";
        break;
    }
}
