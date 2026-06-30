<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InventoryCostService;

$erps = $argv;
array_shift($erps);
if ($erps === []) {
    $erps = ['40500069', '43000123', '43000122', '40500058'];
}

foreach ($erps as $erp) {
    $item = (new InvItemsRepository())->findByErpCode($erp);
    if ($item === null) {
        echo "SKU {$erp} não encontrado\n\n";
        continue;
    }

    $itemId = (int)$item['id'];
    $batch = (float)($item['standard_batch_size'] ?? 0);
    echo "=== {$erp} (id {$itemId}) batch={$batch} ===\n";

    $bd = InventoryCostService::calculateBreakdown($itemId, ['production_efficiency_ratio' => 1.0]);
    $keys = [
        'material_cost', 'operations_cost', 'base_total', 'simulated_total',
        'material_cost_batch', 'operations_cost_batch', 'base_total_batch',
        'simulated_material_cost', 'simulated_operations_cost', 'simulated_total_batch',
        'simulated_cvar_mp_cost', 'cvar_mp_cost', 'labor_hours', 'machine_hours',
    ];
    foreach ($keys as $k) {
        if (isset($bd[$k])) {
            echo str_pad($k, 28) . ': ' . $bd[$k] . "\n";
        }
    }

    $matCount = count($bd['materials'] ?? []);
    $opCount = count($bd['operations'] ?? []);
    echo "materials lines: {$matCount}, operations: {$opCount}\n";

    if ($matCount > 0) {
        echo "Top 5 material lines (line_cost_batch):\n";
        $lines = $bd['materials'];
        usort($lines, static fn($a, $b) => (float)($b['line_cost_batch'] ?? 0) <=> (float)($a['line_cost_batch'] ?? 0));
        foreach (array_slice($lines, 0, 5) as $line) {
            echo '  ' . ($line['component_erp_code'] ?? '?') . ' qty=' . ($line['qty_per_batch'] ?? '?')
                . ' cost_batch=' . ($line['line_cost_batch'] ?? 0) . "\n";
        }
    }

    echo "\n";
}
