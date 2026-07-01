<?php

declare(strict_types=1);

/**
 * Compara CVAR/CFIX do SKU com drivers e planilha Tiaraju (referência).
 * Uso: php scripts/diag_sku_planilha_compare.php 40500052 [period_id]
 */

require __DIR__ . '/bootstrap_app.php';

use App\adms\Helpers\InvCostBatchAdoptedHelper;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InvCostCriterionDriversService;
use App\adms\Models\Services\InvCostFixedAllocationEngine;
use App\adms\Models\Services\InvCostPeriodDriversService;
use App\adms\Models\Services\InvCostPeriodProductionItemsService;
use App\adms\Models\Services\InvCostPeriodSnapshotService;
use App\adms\Models\Services\InventoryCostService;

$erp = $argv[1] ?? '40500052';
$periodId = (int)($argv[2] ?? 4);

$item = (new InvItemsRepository())->findByErpCode($erp);
if ($item === null) {
    fwrite(STDERR, "SKU {$erp} não encontrado\n");
    exit(1);
}
$itemId = (int)$item['id'];

$period = (new InvCostPeriodsRepository())->getOne($periodId);
echo "=== COMPARATIVO PLANILHA × SISTEMA ===\n";
echo "SKU: {$erp} (id {$itemId})\n";
echo "Período: {$periodId} " . ($period['name'] ?? '') . "\n\n";

// Referência planilha (imagem usuário)
$planilha = [
    'preco_venda_liq' => 57.75,
    'cvar_total' => 12.98,
    'cvar_mp' => 9.52,
    'cvar_mae' => 3.47,
    'cfix' => 6.12,
    'custo_pleno' => 19.11,
    'qty_produzida' => 2270.0,
    'lote_adotado' => 2270.0,
    'lotes' => 1.0,
];

echo "--- Referência planilha Tiaraju ---\n";
foreach ($planilha as $k => $v) {
    echo str_pad($k, 20) . ": {$v}\n";
}
echo "\n";

$catalogBatch = (float)($item['standard_batch_size'] ?? 0);
echo "--- Cadastro item ---\n";
echo "standard_batch_size: {$catalogBatch}\n";
echo "average_cost: " . ($item['average_cost'] ?? '') . "\n\n";

$prodRows = (new InvCostPeriodProductionItemsService())->listForPeriod($periodId, null, $erp, bypassSnapshot: true);
$prod = $prodRows[0] ?? [];
$qty = (float)($prod['total_qty'] ?? 0);
$lots = (int)($prod['batches_count'] ?? 1);
$ctx = InvCostBatchAdoptedHelper::resolveProductionContext($prod, $qty, max(1, $lots), $catalogBatch);

echo "--- Pasta 4 (lote adotado) ---\n";
foreach ($ctx as $k => $v) {
    echo str_pad($k, 28) . ": " . (is_float($v) ? $v : json_encode($v)) . "\n";
}
echo "\n";

$costingBatch = (float)$ctx['batch_size_adopted'];
$scenario = [
    'standard_batch_size' => $costingBatch,
    'production_efficiency_ratio' => (float)$ctx['efficiency_ratio'],
];
if (!empty($period['kwh_tariff'])) {
    $scenario['kwh_tariff'] = (float)$period['kwh_tariff'];
}

$bd = InventoryCostService::calculateBreakdown($itemId, $scenario);
echo "--- CVAR (breakdown lote adotado = {$costingBatch}) ---\n";
$cvarKeys = [
    'simulated_cvar_mp_cost', 'simulated_cvar_mae_cost', 'simulated_cvar_energy_cost',
    'simulated_cvar_total', 'material_cost', 'operations_cost',
    'labor_hours', 'machine_hours', 'production_efficiency_ratio',
];
foreach ($cvarKeys as $k) {
    if (isset($bd[$k])) {
        echo str_pad($k, 30) . ': ' . $bd[$k] . "\n";
    }
}
$cvarUnit = (float)($bd['simulated_cvar_mp_cost'] ?? 0)
    + (float)($bd['simulated_cvar_mae_cost'] ?? 0)
    + (float)($bd['simulated_cvar_energy_cost'] ?? 0);
echo str_pad('CVAR/un (soma)', 30) . ': ' . round($cvarUnit, 4) . "\n\n";

$drivers = (new InvCostPeriodDriversService())->aggregateDriversByPeriod($periodId);
$itemDriver = null;
foreach ($drivers['items'] ?? [] as $row) {
    if ((int)($row['inv_item_id'] ?? 0) === $itemId) {
        $itemDriver = $row;
        break;
    }
}
echo "--- Drivers período (HH/HM × lotes) ---\n";
if ($itemDriver) {
    echo 'hh_period: ' . ($itemDriver['hh_period'] ?? 0) . "\n";
    echo 'hm_period: ' . ($itemDriver['hm_period'] ?? 0) . "\n";
    echo 'hh_per_batch: ' . ($itemDriver['hh_per_batch'] ?? 0) . "\n";
    echo 'hm_per_batch: ' . ($itemDriver['hm_per_batch'] ?? 0) . "\n";
    echo 'batches_in_period: ' . ($itemDriver['batches_in_period'] ?? 0) . "\n";
} else {
    echo "SKU sem linha em drivers do período\n";
}
echo "\n";

$criteria = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId);
$critRow = null;
foreach ($criteria['items'] ?? [] as $row) {
    if ((int)($row['inv_item_id'] ?? 0) === $itemId) {
        $critRow = $row;
        break;
    }
}
echo "--- % rateio critérios 1-8 ---\n";
if ($critRow) {
    for ($c = 1; $c <= 8; $c++) {
        $share = (float)($critRow['share_criterion_' . $c] ?? 0);
        $driver = (float)($critRow['driver_' . $c] ?? 0);
        if ($share > 0 || $driver > 0) {
            echo sprintf("  crit %d: driver=%s  share=%.4f%%\n", $c, $driver, $share);
        }
    }
} else {
    echo "SKU sem critérios agregados\n";
}
echo "\n";

$alloc = (new InvCostFixedAllocationEngine())->allocateForItem($periodId, $itemId);
$cfixTotal = (float)($alloc['cfix_total'] ?? 0);
$cfixUnit = $qty > 0 ? round($cfixTotal / $qty, 4) : 0.0;

echo "--- CFIX alocado ---\n";
echo 'cfix_total_periodo: ' . number_format($cfixTotal, 2, ',', '.') . "\n";
echo 'qty_produzida: ' . $qty . "\n";
echo 'cfix/un: ' . $cfixUnit . "\n";
$byCrit = [];
foreach ($alloc['details'] ?? [] as $d) {
    $c = (int)($d['criterion'] ?? 0);
    $byCrit[$c] = ($byCrit[$c] ?? 0) + (float)($d['allocated'] ?? 0);
}
ksort($byCrit);
echo "Por critério:\n";
foreach ($byCrit as $c => $v) {
    echo sprintf("  crit %d: R$ %s\n", $c, number_format($v, 2, ',', '.'));
}
echo "\n";

$snap = (new InvCostPeriodSnapshotService())->listForPeriod($periodId, $erp)[0] ?? [];
echo "--- Snapshot materializado ---\n";
foreach (['cvar_mp_unit', 'cvar_mae_unit', 'cvar_sim_unit', 'cfix_unit', 'full_cost_unit', 'batch_size_adopted'] as $k) {
    if (isset($snap[$k])) {
        echo str_pad($k, 22) . ': ' . $snap[$k] . "\n";
    }
}
echo "\n";

// Snapshot = fonte da tela; breakdown labor_hours/HM segue válido para drivers.
$sysMp = isset($snap['cvar_mp_unit']) ? (float)$snap['cvar_mp_unit'] : (float)($bd['simulated_cvar_mp_cost'] ?? 0);
$sysMae = isset($snap['cvar_mae_unit']) ? (float)$snap['cvar_mae_unit'] : (float)($bd['simulated_cvar_mae_cost'] ?? 0);
$sysCvar = isset($snap['cvar_sim_unit']) ? (float)$snap['cvar_sim_unit'] : $cvarUnit;
$sysCfix = isset($snap['cfix_unit']) ? (float)$snap['cfix_unit'] : $cfixUnit;
$sysFull = isset($snap['full_cost_unit']) ? (float)$snap['full_cost_unit'] : ($sysCvar + $sysCfix);
$sysHh = (float)($itemDriver['hh_per_batch'] ?? $bd['rateio_labor_hours'] ?? $bd['labor_hours'] ?? 0);
$sysHm = (float)($itemDriver['hm_per_batch'] ?? $bd['rateio_machine_hours'] ?? $bd['machine_hours'] ?? 0);
$planilhaHh = 19.030301;
$planilhaHm = 22.7;
$planilhaShareCrit2 = 0.0773;

echo "=== DELTAS (sistema − planilha) ===\n";
echo "(CVAR/CFIX do snapshot materializado — mesma base da aba Resultados)\n";
$rows = [
    ['CVAR MP/un', $sysMp, $planilha['cvar_mp']],
    ['CVAR MAE/un', $sysMae, $planilha['cvar_mae']],
    ['CVAR total/un', $sysCvar, $planilha['cvar_total']],
    ['CFIX/un', $sysCfix, $planilha['cfix']],
    ['Custo pleno/un', $sysFull, $planilha['custo_pleno']],
    ['HH/lote (crit.2)', $sysHh, $planilhaHh],
    ['HM/lote (crit.3)', (float)($itemDriver['hm_per_batch'] ?? $bd['rateio_machine_hours'] ?? 0), $planilhaHm],
];
foreach ($rows as [$label, $sys, $plan]) {
    $delta = round($sys - $plan, 4);
    $pct = $plan > 0 ? round(100 * $delta / $plan, 1) : 0;
    echo sprintf("%-20s plan=%8.2f  sys=%8.2f  delta=%+8.2f (%+.0f%%)\n", $label, $plan, $sys, $delta, $pct);
}
$shareCrit2 = (float)($critRow['share_criterion_2'] ?? 0);
echo sprintf(
    "%-20s plan=%8.4f%% sys=%8.4f%% delta=%+8.4f p.p.\n",
    '% crit. 2 (HH)',
    $planilhaShareCrit2,
    $shareCrit2,
    $shareCrit2 - $planilhaShareCrit2
);

echo "\n--- Hipóteses principais ---\n";
if (abs($sysCfix - $planilha['cfix']) > 1) {
    echo "* CFIX (~" . round($sysCfix / max($planilha['cfix'], 0.01), 1) . "× planilha): HH lote {$sysHh} h vs planilha ~{$planilhaHh} h; participação crit.2 {$shareCrit2}% vs ~{$planilhaShareCrit2}%.\n";
    echo "  Tempos consolidada em horas neste SKU; demais SKUs com minutos SAP (~0,01 h/lote) distorcem o pool.\n";
}
if (abs($sysMp - $planilha['cvar_mp']) > 0.5) {
    echo "* CVAR MP: preços BOM/SAP vs Pasta 6 da planilha.\n";
}
if (abs($sysMae - $planilha['cvar_mae']) > 0.3) {
    echo "* CVAR MAE: revisar componentes de embalagem.\n";
}
if ($sysHh > $planilhaHh * 1.5) {
    echo "* HH: sistema multiplica tempo × qtd MO (BLISTAGEM ×3, CARTONAGEM ×3); planilha ~tempo×eficiência.\n";
}
if ((float)($itemDriver['hm_per_batch'] ?? 0) <= 0 && $planilhaHm > 0) {
    echo "* HM zerado no sistema (sem equipamentos na consolidada); planilha {$planilhaHm} h/lote inclui SECAGEM 2 h.\n";
}
