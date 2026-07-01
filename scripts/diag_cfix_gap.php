<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvItemRouteConsolidatedRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InvCostCriterionDriversService;
use App\adms\Models\Services\InvCostFixedAllocationEngine;

$erp = $argv[1] ?? '40500052';
$periodId = (int)($argv[2] ?? 4);
$itemId = (int)(new InvItemsRepository())->findByErpCode($erp)['id'];

echo "=== ANÁLISE CFIX — {$erp} período {$periodId} ===\n\n";

$drivers = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId);
$totalHh = (float)($drivers['totals']['driver_2'] ?? 0);
$itemDriver = null;
foreach ($drivers['items'] ?? [] as $row) {
    if ((int)($row['inv_item_id'] ?? 0) === $itemId) {
        $itemDriver = $row;
        break;
    }
}
$hhItem = (float)($itemDriver['hh_period'] ?? 0);
$share2 = $totalHh > 0 ? ($hhItem / $totalHh) * 100 : 0;
echo "HH período total (crit 2): " . number_format($totalHh, 2, ',', '.') . " h\n";
echo "HH deste SKU: " . number_format($hhItem, 2, ',', '.') . " h ({$share2}% do período)\n\n";

$alloc = (new InvCostFixedAllocationEngine())->allocateByPeriod($periodId);
$crit2Pool = 0.0;
$cfixItem = (float)($alloc['by_item'][$itemId]['cfix_total'] ?? 0);
$cfixByCrit = [];
foreach ($alloc['by_item'][$itemId]['details'] ?? [] as $detail) {
    $crit = (int)($detail['criterion'] ?? 0);
    $cfixByCrit[$crit] = ($cfixByCrit[$crit] ?? 0) + (float)($detail['allocated'] ?? 0);
    if ($crit === 2) {
        $crit2Pool += (float)($detail['pool_amount'] ?? 0);
    }
}
echo "Pool crit 2 (DRE rateado): R$ " . number_format($crit2Pool, 2, ',', '.') . "\n";
echo "CFIX crit 2 deste SKU: R$ " . number_format((float)($cfixByCrit['2'] ?? $cfixByCrit[2] ?? 0), 2, ',', '.') . "\n";
echo "CFIX total SKU: R$ " . number_format($cfixItem, 2, ',', '.') . "\n\n";

$planCfixUnit = 6.12;
$planCfixTotal = $planCfixUnit * 2270;
$ratio = $cfixItem > 0 ? $cfixItem / $planCfixTotal : 0;
echo "Planilha CFIX total: R$ " . number_format($planCfixTotal, 2, ',', '.') . " ({$planCfixUnit}/un)\n";
echo "Ratio sistema/planilha CFIX: " . number_format($ratio, 2, ',', '.') . "x\n";

if ($share2 > 0 && $crit2Pool > 0) {
    $planCrit2 = (float)($cfixByCrit['2'] ?? $cfixByCrit[2] ?? 0) / $ratio;
    $planHh = $hhItem / $ratio;
    echo "\nSe só HH crit2 explicasse o gap:\n";
    echo "  HH planilha implícito: ~" . number_format($planHh, 2, ',', '.') . " h\n";
    echo "  CFIX crit2 planilha implícito: ~R$ " . number_format($planCrit2, 2, ',', '.') . "\n";
}

echo "\n=== TEMPOS CUSTEIO (rota consolidada ou SAP) ===\n";
$costOps = (new InvItemOperationsRepository())->getByItemForCosting($itemId);
$usesConsolidated = (new InvItemRouteConsolidatedRepository())->hasForItem($itemId);
echo 'Fonte: ' . ($usesConsolidated ? 'CONSOLIDADA' : 'SAP (sem consolidada)') . "\n\n";
foreach ($costOps as $op) {
    $t = (float)($op['time_per_batch_hours'] ?? 0);
    $u = strtoupper((string)($op['time_unit'] ?? 'MIN'));
    $hours = $u === 'H' ? $t : ($t / 60.0);
    echo sprintf(
        "seq=%s %-35s raw=%.4f %s  => %.2f h  MO_header=%d\n",
        $op['sequence'] ?? '?',
        mb_substr((string)($op['operation_name'] ?? ''), 0, 35),
        $t,
        $u,
        $hours,
        (int)($op['operators_qty'] ?? 0)
    );
}

echo "\n=== TEMPOS SAP (rota original — referência) ===\n";
$sapOps = (new InvItemOperationsRepository())->getByItem($itemId);
foreach ($sapOps as $op) {
    $t = (float)($op['time_per_batch_hours'] ?? 0);
    $u = strtoupper((string)($op['time_unit'] ?? 'MIN'));
    $min = $u === 'H' ? $t * 60 : $t;
    echo sprintf(
        "pos=%s %-35s raw=%.4f %s  => %.2f min  MO_header=%d\n",
        $op['sap_pos_text'] ?? $op['sequence'] ?? '?',
        mb_substr((string)($op['operation_name'] ?? ''), 0, 35),
        $t,
        $u,
        $min,
        (int)($op['operators_qty'] ?? 0)
    );
}

echo "\n=== TEMPOS CONSOLIDADA (DB) ===\n";
$cons = (new InvItemRouteConsolidatedRepository())->getByItem($itemId);
foreach ($cons as $op) {
    echo sprintf(
        "seq=%d %-35s raw=%.4f %s  labor=%d  res=%d\n",
        (int)($op['sequence'] ?? 0),
        mb_substr((string)($op['operation_name'] ?? ''), 0, 35),
        (float)($op['time_per_batch_hours'] ?? 0),
        strtoupper((string)($op['time_unit'] ?? 'MIN')),
        count($op['labor_lines'] ?? []),
        count($op['resource_lines'] ?? [])
    );
    foreach ($op['labor_lines'] ?? [] as $ll) {
        $lt = $ll['line_time_minutes'] ?? null;
        echo "    MO id={$ll['inv_labor_role_id']} qty={$ll['qty']}" . ($lt !== null ? " line_min={$lt}" : '') . "\n";
    }
}
