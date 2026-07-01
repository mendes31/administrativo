<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemBomRepository;
use App\adms\Models\Repository\inventory\InvItemRouteConsolidatedRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InvRouteConsolidationService;

require_once __DIR__ . '/../app/adms/Views/inventory/partials/operation_metrics.php';

$erp = $argv[1] ?? '40500052';
$itemId = (int)(new InvItemsRepository())->findByErpCode($erp)['id'];
$batch = 2270.0;

echo "=== ROTA CONSOLIDADA / HH — {$erp} ===\n";
$cons = (new InvItemRouteConsolidatedRepository())->getByItem($itemId);
$totalHh = 0.0;
$totalHm = 0.0;
$totalMin = 0.0;
foreach ($cons as $op) {
    $labor = invNormalizeLaborLinesForDrivers($op['labor_lines'] ?? []);
    $res = invNormalizeMachineResourceLinesForDrivers($op['resource_lines'] ?? []);
    $m = invOperationMetrics($op);
    $timeMin = (float)$m['time_minutes'];
    $d = invOperationDriverHours($labor, $res, $timeMin, 1);
    $hh = (float)$d['labor_hours'];
    $hm = (float)$d['machine_hours'];
    $totalHh += $hh;
    $totalHm += $hm;
    $totalMin += $timeMin;
    echo sprintf(
        "%-40s time=%6.2f min (%s)  HH=%6.2f h  HM=%6.2f h  MO=%d eq=%d\n",
        mb_substr((string)($op['operation_name'] ?? ''), 0, 40),
        $timeMin,
        $op['time_unit'] ?? 'MIN',
        $hh,
        $hm,
        count($labor),
        count($res)
    );
}
echo sprintf("\nTOTAL: time=%.2f min  HH=%.2f h  HM=%.2f h\n", $totalMin, $totalHh, $totalHm);

echo "\n=== BOM (top linhas por custo/lote) ===\n";
$bom = (new InvItemBomRepository())->getByItem($itemId);
$lines = [];
foreach ($bom as $line) {
    $cost = InvItemBomRepository::computeLineMaterialCost($line);
    $lines[] = [
        'code' => $line['component_erp_code'] ?? $line['component_code'] ?? '?',
        'qty' => (float)($line['quantity_per_batch'] ?? 0),
        'unit_cost' => InvItemBomRepository::resolveLineUnitCost($line),
        'line_cost' => $cost,
        'group' => $line['component_group_name'] ?? '',
    ];
}
usort($lines, static fn($a, $b) => $b['line_cost'] <=> $a['line_cost']);
$mp = 0.0;
$mae = 0.0;
foreach ($lines as $l) {
    $g = strtoupper((string)$l['group']);
    if (str_contains($g, 'EMB') || str_contains($g, 'MAE')) {
        $mae += $l['line_cost'];
    } else {
        $mp += $l['line_cost'];
    }
}
echo sprintf("MP total/lote: %.4f  MAE total/lote: %.4f  SOMA: %.4f\n", $mp, $mae, $mp + $mae);
echo sprintf("MP/un (÷%.0f): %.4f  MAE/un: %.4f\n", $batch, $mp / $batch, $mae / $batch);
echo "\nTop 8 componentes:\n";
foreach (array_slice($lines, 0, 8) as $l) {
    echo sprintf(
        "  %s  q=%.6f  u=%.6f  linha=%.4f\n",
        $l['code'],
        $l['qty'],
        $l['unit_cost'],
        $l['line_cost']
    );
}
