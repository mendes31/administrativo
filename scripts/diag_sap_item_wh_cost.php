<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Services\SapReportApiService;

$erp = $argv[1] ?? '10500015';
$safe = str_replace("'", "''", $erp);
$sap = new SapReportApiService();

echo "=== OITM {$erp} ===\n";
$r = $sap->execute("SELECT \"ItemCode\", \"DfltWH\", \"AvgPrice\" FROM OITM WHERE \"ItemCode\" = '{$safe}'");
foreach ($r['data'] ?? [] as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

echo "\n=== OITW {$erp} (AvgPrice > 0) ===\n";
$r = $sap->execute("SELECT \"WhsCode\", \"AvgPrice\", \"OnHand\" FROM OITW WHERE \"ItemCode\" = '{$safe}' AND \"AvgPrice\" > 0 ORDER BY \"WhsCode\"");
foreach ($r['data'] ?? [] as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
