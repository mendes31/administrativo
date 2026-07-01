<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Services\InventorySapSyncService;
use App\adms\Models\Services\SapReportApiService;

$erp = $argv[1] ?? '40500052';
$sap = new SapReportApiService();
$service = new InventorySapSyncService();
$ref = new ReflectionClass(InventorySapSyncService::class);

$fetchRoute = $ref->getMethod('fetchSapRouteRowsWithFallback');
$fetchRoute->setAccessible(true);
$rows = $fetchRoute->invoke($service, $sap, $erp);

echo "SAP route rows for {$erp}: " . count($rows) . "\n";
echo "pos_id;pos_text;sort_id;master_pos_id;codigo\n";
foreach ($rows as $row) {
    echo implode(';', [
        (string)($row['pos_id'] ?? $row['POS_ID'] ?? ''),
        (string)($row['pos_text'] ?? $row['POS_TEXT'] ?? ''),
        (string)($row['sort_id'] ?? $row['SortId'] ?? $row['SORT_ID'] ?? ''),
        (string)($row['master_pos_id'] ?? $row['MASTER_POS_ID'] ?? ''),
        (string)($row['codigo'] ?? $row['AG_ID'] ?? ''),
    ]) . "\n";
}
