<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InvCostAnalysisDriverService;

$erp = $argv[1] ?? '43000043';
$batches = (int)($argv[2] ?? 56);
$efficiency = isset($argv[3]) ? (float)$argv[3] : 0.9812;

$item = (new InvItemsRepository())->findByErpCode($erp);
if ($item === null) {
    echo "Item {$erp} não encontrado.\n";
    exit(1);
}

$itemId = (int)$item['id'];
$driver = new InvCostAnalysisDriverService();
$periodItem = ['complexity_level' => 'baixa'];
$metrics = $driver->metricsForItem($itemId, $batches, $periodItem, $efficiency);

echo "ERP: {$erp} (id {$itemId})\n";
echo 'MP lines: ' . ($metrics['mp_lines'] ?? 0) . "\n";
echo 'MAE lines: ' . ($metrics['mae_lines'] ?? 0) . "\n";
echo 'Lines per batch: ' . ($metrics['analysis_lines_per_batch'] ?? 0) . "\n";
echo 'Batches: ' . $batches . "\n";
echo 'Analyses: ' . ($metrics['analysis_count_total'] ?? 0) . "\n";
echo 'Factor: ' . ($metrics['complexity_factor'] ?? 0) . "\n";
echo 'Driver 6 (Compl×Anál): ' . ($metrics['driver_6'] ?? 0) . "\n";
echo 'Expected analyses: ' . (($metrics['analysis_lines_per_batch'] ?? 0) * $batches) . "\n";
echo 'Expected driver 6: ' . (($metrics['complexity_factor'] ?? 0) * ($metrics['analysis_lines_per_batch'] ?? 0) * $batches) . "\n";
