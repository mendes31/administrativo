<?php
declare(strict_types=1);

/**
 * Sincronização SAP de itens via CLI (evita timeout do navegador).
 *
 * Uso:
 *   php scripts/sync_inventory_items_sap.php              # diff catálogo
 *   php scripts/sync_inventory_items_sap.php --continue   # repete até fim ou limite
 *   php scripts/sync_inventory_items_sap.php --full       # completa + purge
 *   php scripts/sync_inventory_items_sap.php --code=43000045
 *   php scripts/sync_inventory_items_sap.php --group=400
 *   php scripts/sync_inventory_items_sap.php --period=4   # SKUs do período sem cadastro
 *   php scripts/sync_inventory_items_sap.php --udf        # forma farmacêutica + linha (PA/PI)
 *   php scripts/sync_inventory_items_sap.php --udf --group=400
 */
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InvCostProductionAggregationService;
use App\adms\Models\Services\InventorySapSyncService;

$full = false;
$code = null;
$group = null;
$continue = false;
$maxLoops = 40;
$periodId = 0;
$udfOnly = false;

foreach ($argv ?? [] as $arg) {
    if ($arg === '--full') {
        $full = true;
    } elseif ($arg === '--continue') {
        $continue = true;
    } elseif ($arg === '--udf') {
        $udfOnly = true;
    } elseif (str_starts_with($arg, '--continue=')) {
        $continue = true;
        $maxLoops = max(1, (int)substr($arg, 11));
    } elseif (str_starts_with($arg, '--code=')) {
        $code = trim(substr($arg, 7));
    } elseif (str_starts_with($arg, '--group=')) {
        $group = trim(substr($arg, 8));
    } elseif (str_starts_with($arg, '--period=')) {
        $periodId = max(0, (int)substr($arg, 9));
    }
}

$service = new InventorySapSyncService();

if ($udfOnly) {
    echo 'Atualizando forma farmacêutica e linha (UDF SAP)...' . ($group !== null && $group !== '' ? " grupo {$group}" : '') . "\n";
    $result = $service->syncMissingUdfFields(($group ?? '') !== '' ? $group : null);
    echo ($result['success'] ? 'OK' : 'FALHA') . ': ' . ($result['message'] ?? '') . "\n";
    exit(!empty($result['success']) ? 0 : 1);
}

if ($periodId > 0) {
    echo "Sync SKUs sem cadastro no período {$periodId}...\n";
    $aggregation = (new InvCostProductionAggregationService())->aggregateByPeriod($periodId);
    $itemsRepo = new InvItemsRepository();
    $missing = [];
    foreach ($aggregation['items'] ?? [] as $row) {
        $erp = trim((string)($row['erp_code'] ?? ''));
        if ($erp === '') {
            continue;
        }
        $itemId = (int)($row['inv_item_id'] ?? 0);
        if ($itemId <= 0 && $itemsRepo->findByErpCode($erp) === null) {
            $missing[(string)$erp] = true;
        }
    }
    echo count($missing) . " SKU(s) sem cadastro.\n";
    $totals = ['created' => 0, 'updated' => 0, 'failed' => 0];
    foreach (array_keys($missing) as $erp) {
        $erp = (string)$erp;
        echo "  → {$erp}... ";
        $r = $service->syncItemsAndCosts(false, $erp, null);
        if (!empty($r['success']) && (($r['created'] ?? 0) + ($r['updated'] ?? 0)) > 0) {
            $totals['created'] += (int)($r['created'] ?? 0);
            $totals['updated'] += (int)($r['updated'] ?? 0);
            echo "OK\n";
        } elseif (!empty($r['success'])) {
            echo "sem mudança\n";
        } else {
            $totals['failed']++;
            echo 'FALHA: ' . ($r['message'] ?? '') . "\n";
        }
        usleep(500000);
    }
    echo sprintf("Concluído. Novos: %d | Alterados: %d | Falhas: %d\n", $totals['created'], $totals['updated'], $totals['failed']);
    exit($totals['failed'] > 0 ? 1 : 0);
}

if ($code !== null && $code !== '') {
    $full = false;
    $group = null;
    echo "Sync item {$code}...\n";
    $result = $service->syncItemsAndCosts($full, $code, null);
} elseif ($group !== null && $group !== '') {
    $full = false;
    echo "Sync grupo {$group}...\n";
    $loop = 0;
    do {
        $loop++;
        if ($loop > 1) {
            echo "--- Retomada {$loop}/{$maxLoops} ---\n";
            sleep(empty($result['success']) ? 8 : 3);
        }
        $result = $service->syncItemsAndCosts(false, null, $group);
        echo ($result['success'] ? 'OK' : 'FALHA') . ': ' . ($result['message'] ?? '') . "\n";
        if (!empty($result['success']) && empty($result['partial'])) {
            break;
        }
    } while ($continue && $loop < $maxLoops && (!empty($result['partial']) || empty($result['success'])));
} elseif ($continue) {
    echo "Sync por diff (modo continuar, até {$maxLoops} execuções)...\n";
    $loop = 0;
    $result = ['success' => false, 'partial' => true];
    do {
        $loop++;
        if ($loop > 1) {
            echo "--- Retomada {$loop}/{$maxLoops} ---\n";
            sleep(empty($result['success']) ? 8 : 3);
        } else {
            echo "--- Execução {$loop}/{$maxLoops} ---\n";
        }
        $result = $service->syncItemsAndCosts($full);
        echo ($result['success'] ? 'OK' : 'FALHA') . ': ' . ($result['message'] ?? '') . "\n";
        if (!empty($result['success']) && empty($result['partial'])) {
            break;
        }
    } while ($loop < $maxLoops);
} else {
    echo ($full ? 'Sync COMPLETA' : 'Sync por diff') . " iniciada...\n";
    $result = $service->syncItemsAndCosts($full);
}

echo json_encode(array_diff_key($result, ['message' => true]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

exit(!empty($result['success']) ? 0 : 1);
