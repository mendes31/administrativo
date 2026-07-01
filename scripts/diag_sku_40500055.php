<?php
require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InvCostFixedAllocationEngine;
use App\adms\Models\Services\InvCostProductionAggregationService;
use App\adms\Models\Services\InventoryCostService;

$erp = '40500055';
$periodId = 4;
$item = (new InvItemsRepository())->findByErpCode($erp);
$itemId = (int)$item['id'];

$agg = (new InvCostProductionAggregationService())->aggregateByPeriod($periodId);
$prod = null;
foreach ($agg['items'] as $row) {
    if (($row['erp_code'] ?? '') === $erp) {
        $prod = $row;
        break;
    }
}

$eff = 0.9514;
if ($prod) {
    $effService = new App\adms\Models\Services\InvCostProductionEfficiencyService();
    $effData = $effService->aggregateForItemInPeriod($itemId, $erp, '2025-01-01', '2025-12-31', null, $periodId);
    if ($effData) {
        $eff = (float)$effData['efficiency_ratio'];
    }
}

echo "Produção: qty=" . ($prod['qty_produced'] ?? '?') . " lotes=" . ($prod['batches_count'] ?? '?') . " eff={$eff}\n";
echo "standard_batch_size=" . ($item['standard_batch_size'] ?? '?') . "\n\n";

$bd = InventoryCostService::calculateBreakdown($itemId, ['production_efficiency_ratio' => $eff]);
$keys = [
    'simulated_total', 'simulated_cvar_mp_cost', 'simulated_cvar_mae_cost', 'simulated_cvar_energy_cost',
    'simulated_total_batch', 'simulated_cvar_mp_cost_batch', 'simulated_cvar_mae_cost_batch',
    'cvar_mp_cost', 'cvar_mae_cost', 'material_cost', 'operations_cost',
];
foreach ($keys as $k) {
    echo str_pad($k, 32) . ($bd[$k] ?? '—') . "\n";
}

$cfix = (new InvCostFixedAllocationEngine())->allocateForItem($periodId, $itemId);
$qty = (float)($prod['qty_produced'] ?? 1);
echo "\nCFIX total período: " . ($cfix['cfix_total'] ?? 0) . "\n";
echo "CFIX/SKU (qty {$qty}): " . round(($cfix['cfix_total'] ?? 0) / max($qty, 1), 4) . "\n";
echo "Custo pleno/SKU: " . round((float)$bd['simulated_total'] + (float)($bd['simulated_cvar_energy_cost'] ?? 0) + ($cfix['cfix_total'] ?? 0) / max($qty, 1), 4) . "\n";

echo "\n--- Comparação planilha (por unidade vendável) ---\n";
echo "Planilha CVAR: 7.10 | Sistema CVAR sim (simulated_total): " . round((float)$bd['simulated_total'], 4) . "\n";
echo "Planilha CVAR batch? sistema batch total MP+ops: " . round((float)$bd['simulated_total_batch'], 2) . "\n";
echo "Planilha CFIX: 13.07 | Sistema CFIX/SKU: " . round(($cfix['cfix_total'] ?? 0) / max($qty, 1), 2) . "\n";
