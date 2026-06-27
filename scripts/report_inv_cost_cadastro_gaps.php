<?php

declare(strict_types=1);

/**
 * Relatório de lacunas de cadastro × planilha Tiaraju (período de custeio).
 *
 * Uso:
 *   php scripts/report_inv_cost_cadastro_gaps.php [periodId] [--csv=caminho.csv]
 *
 * Saída: resumo no console + CSV opcional (UTF-8 BOM, ponto e vírgula).
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InvCostAnalysisDriverService;
use App\adms\Models\Services\InvCostCriterionDriversService;
use App\adms\Models\Services\InvCostPeriodProductionItemsService;
use App\adms\Models\Services\InvCostProductionAggregationService;
use App\adms\Models\Services\InventoryCostService;

$periodId = 0;
$csvPath = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--csv=')) {
        $csvPath = substr($arg, 6);
        continue;
    }
    if (is_numeric($arg)) {
        $periodId = (int)$arg;
    }
}

if ($periodId <= 0) {
    fwrite(STDERR, "Informe o ID do período: php scripts/report_inv_cost_cadastro_gaps.php 4\n");
    exit(1);
}

$aggregation = (new InvCostProductionAggregationService())->aggregateByPeriod($periodId);
$prodRows = $aggregation['items'] ?? [];
$periodName = (string)($aggregation['period']['name'] ?? $periodId);

$ids = [];
$erpCodes = [];
foreach ($prodRows as $prod) {
    $id = (int)($prod['inv_item_id'] ?? 0);
    if ($id > 0) {
        $ids[] = $id;
    }
    $erp = trim((string)($prod['erp_code'] ?? ''));
    if ($erp !== '') {
        $erpCodes[] = $erp;
    }
}

$itemMaps = (new InvItemsRepository())->getDetailedMapForProduction($ids, $erpCodes);
$analysisDriver = new InvCostAnalysisDriverService();
$criteria = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId);
$shareByErp = [];
$shareById = [];
foreach ($criteria['items'] ?? [] as $cRow) {
    $cid = (int)($cRow['inv_item_id'] ?? 0);
    if ($cid > 0) {
        $shareById[$cid] = $cRow;
    }
    $erpK = mb_strtoupper(trim((string)($cRow['erp_code'] ?? '')), 'UTF-8');
    if ($erpK !== '') {
        $shareByErp[$erpK] = $cRow;
    }
}

$rows = [];
$counters = [
    'total' => 0,
    'pa' => 0,
    'sem_cadastro' => 0,
    'sem_bom' => 0,
    'sem_rota' => 0,
    'sem_linha' => 0,
    'sem_energia' => 0,
    'driver_6_zero' => 0,
    'com_pendencia' => 0,
];

foreach ($prodRows as $prod) {
    $counters['total']++;
    $itemId = (int)($prod['inv_item_id'] ?? 0);
    $erpCode = trim((string)($prod['erp_code'] ?? ''));
    $erpKey = mb_strtoupper($erpCode, 'UTF-8');

    $itemMeta = null;
    if ($itemId > 0 && isset($itemMaps['by_id'][$itemId])) {
        $itemMeta = $itemMaps['by_id'][$itemId];
    } elseif ($erpKey !== '' && isset($itemMaps['by_erp'][$erpKey])) {
        $itemMeta = $itemMaps['by_erp'][$erpKey];
        $itemId = (int)($itemMeta['id'] ?? 0);
    }

    $categoryName = is_array($itemMeta) ? trim((string)($itemMeta['category_name'] ?? '')) : '';
    $isPa = InvCostPeriodProductionItemsService::isProdutoAcabado($categoryName, $erpCode);
    if ($isPa) {
        $counters['pa']++;
    }

    $description = trim((string)($prod['description'] ?? ($itemMeta['description'] ?? '')));
    $batches = max(0, (int)($prod['batches_count'] ?? 0));

    $gaps = [];
    if ($itemId <= 0) {
        $gaps[] = 'sem_cadastro';
        $counters['sem_cadastro']++;
    }

    $mpLines = 0;
    $maeLines = 0;
    $hhBatch = 0.0;
    $hmBatch = 0.0;
    $powerKw = 0.0;

    if ($itemId > 0) {
        $mpLines = $analysisDriver->countMpLines($itemId);
        $maeLines = $analysisDriver->countMaeLines($itemId);
        $bd = InventoryCostService::calculateBreakdown($itemId, []);
        $hhBatch = (float)($bd['labor_hours'] ?? 0);
        $hmBatch = (float)($bd['machine_hours'] ?? 0);
        foreach ($bd['operations'] ?? [] as $op) {
            foreach ($op['resource_lines'] ?? [] as $res) {
                $powerKw += max(0.0, (float)($res['power_kw'] ?? 0)) * max(1, (int)($res['qty'] ?? 1));
            }
        }
    }

    if ($itemId > 0 && $mpLines + $maeLines <= 0) {
        $gaps[] = 'sem_bom_mp_mae';
        $counters['sem_bom']++;
    }
    if ($itemId > 0 && $hhBatch <= 0 && $hmBatch <= 0) {
        $gaps[] = 'sem_rota_hh_hm';
        $counters['sem_rota']++;
    }

    $productionLine = is_array($itemMeta) ? mb_strtoupper(trim((string)($itemMeta['production_line'] ?? '')), 'UTF-8') : '';
    if ($itemId > 0 && $productionLine === '') {
        $gaps[] = 'sem_linha';
        $counters['sem_linha']++;
    }

    $energyClass = is_array($itemMeta) ? mb_strtoupper(trim((string)($itemMeta['energy_class'] ?? '')), 'UTF-8') : '';
    if ($itemId > 0 && $energyClass === '') {
        $gaps[] = 'sem_classe_energia';
        $counters['sem_energia']++;
    }

    $critRow = $itemId > 0 && isset($shareById[$itemId])
        ? $shareById[$itemId]
        : ($erpKey !== '' && isset($shareByErp[$erpKey]) ? $shareByErp[$erpKey] : null);
    $driver6 = (float)($critRow['driver_6'] ?? 0);
    $share6 = (float)($critRow['share_criterion_6'] ?? 0);
    if ($batches > 0 && $driver6 <= 0) {
        $gaps[] = 'driver_6_zero';
        $counters['driver_6_zero']++;
    }

    if ($gaps !== []) {
        $counters['com_pendencia']++;
    }

    $rows[] = [
        'erp_code' => $erpCode,
        'description' => $description,
        'is_pa' => $isPa ? 'SIM' : 'NAO',
        'inv_item_id' => $itemId > 0 ? (string)$itemId : '',
        'batches' => (string)$batches,
        'mp_lines' => (string)$mpLines,
        'mae_lines' => (string)$maeLines,
        'hh_per_batch' => $hhBatch > 0 ? number_format($hhBatch, 4, '.', '') : '0',
        'hm_per_batch' => $hmBatch > 0 ? number_format($hmBatch, 4, '.', '') : '0',
        'power_kw_route' => $powerKw > 0 ? number_format($powerKw, 4, '.', '') : '0',
        'production_line' => $productionLine,
        'crit7_elegivel' => \App\adms\Helpers\InvCostProductionLineHelper::isEligibleForDirectEnergy($productionLine) ? 'SIM' : 'NAO',
        'energy_class' => $energyClass,
        'complexity' => is_array($itemMeta) ? (string)($itemMeta['complexity_level'] ?? '') : '',
        'driver_6' => number_format($driver6, 4, '.', ''),
        'share_criterion_6_pct' => number_format($share6, 4, '.', ''),
        'pendencias' => implode('|', $gaps),
        'edit_url' => $itemId > 0 ? ($_ENV['URL_ADM'] ?? '') . 'update-inventory-item/' . $itemId : '',
    ];
}

usort($rows, static fn(array $a, array $b): int => strcasecmp($a['description'], $b['description']));

echo "=== Relatório cadastro × custeio fabril ===\n";
echo "Período: {$periodName} (ID {$periodId})\n";
echo 'SKUs com produção: ' . $counters['total'] . "\n";
echo 'Produto acabado (PA): ' . $counters['pa'] . "\n";
echo 'Com alguma pendência: ' . $counters['com_pendencia'] . "\n";
echo "\nDetalhe:\n";
echo '  Sem cadastro ERP→item: ' . $counters['sem_cadastro'] . "\n";
echo '  Sem BOM MP/MAE: ' . $counters['sem_bom'] . "\n";
echo '  Sem rota HH/HM: ' . $counters['sem_rota'] . "\n";
echo '  Sem linha TERCEIRO/TIARAJU: ' . $counters['sem_linha'] . "\n";
echo '  Sem classe energia: ' . $counters['sem_energia'] . "\n";
echo '  Driver crit.6 zero (com lotes): ' . $counters['driver_6_zero'] . "\n";

$paPend = array_filter($rows, static fn(array $r): bool => $r['is_pa'] === 'SIM' && $r['pendencias'] !== '');
echo "\nPA com pendência: " . count($paPend) . "\n";

if ($csvPath === null) {
    $csvPath = dirname(__DIR__) . '/storage/reports/inv_cost_cadastro_gaps_period_' . $periodId . '.csv';
}

$dir = dirname($csvPath);
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$headers = array_keys($rows[0] ?? []);
$fp = fopen($csvPath, 'wb');
if ($fp === false) {
    fwrite(STDERR, "Não foi possível gravar CSV em {$csvPath}\n");
    exit(1);
}
fwrite($fp, "\xEF\xBB\xBF");
fputcsv($fp, $headers, ';');
foreach ($rows as $row) {
    fputcsv($fp, array_values($row), ';');
}
fclose($fp);

echo "\nCSV: {$csvPath}\n";
