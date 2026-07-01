<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';
require __DIR__ . '/analyze_cost_files.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InventoryCostService;

$erp = $argv[1] ?? '40500052';
$batch = 2270.0;
$itemId = (int)(new InvItemsRepository())->findByErpCode($erp)['id'];

$path = 'C:/Users/rafael.oliveira/Desktop/Custos Original/Planilha_de_Custeio_Tiaraju_202605_Excel2016_REVISAO_COMPLETA.xlsx';
$sheets = readXlsxSheets($path);
$pasta6 = $sheets['Pasta 6 - CUSTOS MP E MAES'] ?? [];

$bd = InventoryCostService::calculateBreakdown($itemId, ['standard_batch_size' => $batch]);

echo "=== CVAR sistema (lote {$batch}) ===\n";
echo "MP/un: " . ($bd['cvar_mp_cost'] ?? 0) . "\n";
echo "MAE/un: " . ($bd['cvar_mae_cost'] ?? 0) . "\n";
echo "Total/un: " . (($bd['cvar_mp_cost'] ?? 0) + ($bd['cvar_mae_cost'] ?? 0)) . "\n";
echo "HH: " . ($bd['labor_hours'] ?? 0) . "  HM: " . ($bd['machine_hours'] ?? 0) . "\n\n";

echo "=== Materiais sistema (top 10) ===\n";
foreach (array_slice($bd['materials'] ?? [], 0, 12) as $m) {
    echo sprintf(
        "%s %-12s q=%.6f u=%.4f linha/lote=%.4f grupo=%s\n",
        $m['component_code'] ?? '?',
        '',
        (float)($m['quantity_per_batch'] ?? 0),
        (float)($m['unit_cost'] ?? 0),
        (float)($m['line_cost_batch'] ?? $m['line_cost'] ?? 0),
        $m['group_name'] ?? ''
    );
}

// Planilha CUSTEIO FABRIL MP/MAE
$cust = $sheets['CUSTEIO FABRIL'];
echo "\n=== Planilha CUSTEIO FABRIL col F ===\n";
foreach ([2299 => 'CVAR MAE', 2300 => 'CVAR total', 1554 => 'CVAR MP', 7 => 'row7', 8 => 'row8'] as $r => $lbl) {
    $v = $cust[$r]['F'] ?? null;
    if ($v !== null) {
        echo "L{$r} {$lbl}: {$v}\n";
    }
}

// HH planilha L1090
echo "\nL1090 HH (planilha): " . ($cust[1090]['F'] ?? '?') . "\n";
echo "L1146 share crit2: " . ($cust[1146]['F'] ?? '?') . "\n";
echo "L1185 HM/lote: " . ($cust[1185]['F'] ?? '?') . "\n";
echo "L1187 share crit3: " . ($cust[1187]['F'] ?? '?') . "\n";

// HH if only 1 MO per op (wall clock)
echo "\n=== HH simulado (1 MO/op, tempos consolidada) ===\n";
require_once __DIR__ . '/../app/adms/Views/inventory/partials/operation_metrics.php';
use App\adms\Models\Repository\inventory\InvItemRouteConsolidatedRepository;

$cons = (new InvItemRouteConsolidatedRepository())->getByItem($itemId);
$hh1 = 0.0;
foreach ($cons as $op) {
    $m = invOperationMetrics($op);
    $min = (float)$m['time_minutes'];
    if ($min > 0) {
        $hh1 += $min / 60.0;
    }
}
echo "Wall-clock (sem × MO): {$hh1} h\n";
echo "Sistema atual: " . ($bd['labor_hours'] ?? 0) . " h\n";
echo "Planilha L1090: 19.030301 h\n";
echo "HM planilha (soma L1151-1184): 22.7 h\n";
