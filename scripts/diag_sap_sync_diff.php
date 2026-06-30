<?php

declare(strict_types=1);

/**
 * Diagnóstico: por que itens aparecem como "Alterados" na sync SAP?
 * Compara snapshot local vs payload que a sync geraria (sem gravar).
 *
 * Uso: php scripts/diag_sap_sync_diff.php [limite]
 */

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvCategoriesRepository;
use App\adms\Models\Repository\inventory\InvUnitsRepository;
use App\adms\Models\Repository\inventory\InvPharmaFormsRepository;
use App\adms\Models\Services\InventorySapSyncService;
use App\adms\Models\Services\SapReportApiService;

$limit = max(1, min(500, (int)($argv[1] ?? 50)));

$itemsRepo = new InvItemsRepository();
$snapshot = $itemsRepo->getSapSyncSnapshot();
$service = new InventorySapSyncService();
$ref = new ReflectionClass(InventorySapSyncService::class);

$normalizeRow = $ref->getMethod('normalizeOitmCatalogRow');
$normalizeRow->setAccessible(true);
$processPayload = static function (
    InventorySapSyncService $service,
    ReflectionClass $ref,
    array $row,
    array $existing,
    InvCategoriesRepository $categoriesRepo,
    InvUnitsRepository $unitsRepo,
    InvPharmaFormsRepository $pharmaFormsRepo,
    int $defaultUnitId
): array {
    $infer = $ref->getMethod('inferItemTypeFromCategory');
    $infer->setAccessible(true);
    $normalizeCat = $ref->getMethod('normalizeCategoryName');
    $normalizeCat->setAccessible(true);
    $extractCost = $ref->getMethod('extractSapCost');
    $extractCost->setAccessible(true);
    $normalizeDate = $ref->getMethod('normalizeSapUpdateDate');
    $normalizeDate->setAccessible(true);
    $resolvePharma = $ref->getMethod('resolvePharmaFormId');
    $resolvePharma->setAccessible(true);
    $extractNum = $ref->getMethod('extractSapNumericField');
    $extractNum->setAccessible(true);
    $resolveAdmin = $ref->getMethod('resolveSapAdminType');
    $resolveAdmin->setAccessible(true);

    $categoryName = $normalizeCat->invoke($service, (string)($row['ItemGroupName'] ?? 'Geral'));
    $categoryId = $categoriesRepo->findOrCreateByName($categoryName);
    $uomCode = strtoupper(trim((string)($row['InvntryUom'] ?? '')));
    $unitId = $unitsRepo->findIdByCode($uomCode) ?? $defaultUnitId;
    $itemType = $infer->invoke($service, $categoryName);
    $isPaPi = in_array($itemType, ['PA', 'PI'], true);
    $sapCost = $extractCost->invoke($service, $row);
    $active = strtoupper((string)($row['validFor'] ?? 'Y')) === 'Y' ? 1 : 0;
    $sapUpdateDate = $normalizeDate->invoke($service, $row['UpdateDate'] ?? null);
    $pharmaFormId = $isPaPi
        ? $ref->getMethod('resolvePharmaFormIdForItemSync')->invoke(
            $service,
            $pharmaFormsRepo,
            $row['U_FormaFarma'] ?? null,
            $existing
        )
        : null;
    $productionLine = $isPaPi
        ? $ref->getMethod('resolveProductionLineForItemSync')->invoke(
            $service,
            $row['U_LinhaProduto'] ?? null,
            $existing
        )
        : null;
    $shouldUpdateCost = in_array($itemType, ['MP', 'EMB'], true);
    $resolvedCost = $ref->getMethod('resolveSapItemCostForSync')->invoke(
        $service,
        $row,
        $existing,
        $shouldUpdateCost
    );
    $minLevel = $extractNum->invoke($service, $row, 'MinLevel');
    $maxLevel = $extractNum->invoke($service, $row, 'MaxLevel');
    $minOrderQty = $extractNum->invoke($service, $row, 'MinOrdrQty');
    $adminType = $resolveAdmin->invoke($service, $row);
    $shouldUpdateCost = in_array($itemType, ['MP', 'EMB'], true);

    return [
        'description' => trim((string)($row['ItemName'] ?? '')) ?: trim((string)($row['ItemCode'] ?? '')),
        'inv_unit_id' => (int)$unitId,
        'inv_category_id' => (int)$categoryId,
        'average_cost' => $resolvedCost,
        'last_cost' => $resolvedCost,
        'active' => $active,
        'production_line' => $isPaPi ? $productionLine : ($existing['production_line'] ?? null),
        'inv_pharma_form_id' => $isPaPi ? $pharmaFormId : ($existing['inv_pharma_form_id'] ?? null),
        'sap_update_date' => $sapUpdateDate,
        'admin_type' => $adminType,
        'min_stock' => $minLevel ?? (float)($existing['min_stock'] ?? 0),
        'max_stock' => $maxLevel ?? (float)($existing['max_stock'] ?? 0),
        'standard_batch_size' => $ref->getMethod('resolveStandardBatchSizeFromSap')->invoke(
            $service,
            $minOrderQty,
            (float)($existing['standard_batch_size'] ?? 1)
        ),
    ];
};

$diffFields = static function (array $existing, array $payload): array {
    $compare = static function (mixed $a, mixed $b): bool {
        if (is_numeric($a) || is_numeric($b)) {
            return round((float)$a, 6) !== round((float)$b, 6);
        }

        return trim((string)$a) !== trim((string)$b);
    };
    $out = [];
    foreach (['description', 'inv_unit_id', 'inv_category_id', 'average_cost', 'last_cost', 'active', 'production_line', 'inv_pharma_form_id', 'sap_update_date', 'admin_type', 'min_stock', 'max_stock', 'standard_batch_size'] as $field) {
        if ($compare($existing[$field] ?? null, $payload[$field] ?? null)) {
            $out[$field] = ['local' => $existing[$field] ?? null, 'payload' => $payload[$field] ?? null];
        }
    }

    return $out;
};

try {
    $sap = new SapReportApiService();
} catch (Throwable $e) {
    echo 'SAP indisponível: ' . $e->getMessage() . "\n";
    exit(1);
}

$resolveGroups = $ref->getMethod('resolveAllowedSapGroupCodes');
$resolveGroups->setAccessible(true);
$resolveGroups->invoke($service, $sap);
$groupMap = $ref->getMethod('fetchSapItemGroupsMap');
$groupMap->setAccessible(true);
$groups = $groupMap->invoke($service, $sap);

$selectCols = $ref->getMethod('sapOitmCatalogSelectColumns');
$selectCols->setAccessible(true);
$cols = $selectCols->invoke($service, 'T0');

$categoriesRepo = new InvCategoriesRepository();
$unitsRepo = new InvUnitsRepository();
$pharmaFormsRepo = new InvPharmaFormsRepository();
$defaultUnitId = $unitsRepo->findIdByCode('UN') ?? (int)(($unitsRepo->getAll(1, 1)[0]['id'] ?? 0));

$computeHash = $ref->getMethod('computeItemHashFromSapRow');
$computeHash->setAccessible(true);
$differsLocal = $ref->getMethod('sapRowDiffersFromLocalItem');
$differsLocal->setAccessible(true);

$examined = 0;
$wouldUpdate = 0;
$fieldCounts = [];
$examples = [];

foreach ($snapshot as $erpKey => $local) {
    if ($examined >= $limit) {
        break;
    }
    $erp = trim((string)($local['erp_code'] ?? ''));
    if ($erp === '') {
        continue;
    }

    $safe = str_replace("'", "''", $erp);
    $sql = 'SELECT ' . $cols . ' FROM OITM T0 WHERE T0."ItemCode" = \'' . $safe . '\' LIMIT 1';
    try {
        $rows = $sap->execute($sql)['data'] ?? [];
    } catch (Throwable) {
        continue;
    }
    if (!is_array($rows[0] ?? null)) {
        continue;
    }

    $examined++;
    $row = $normalizeRow->invoke($service, $rows[0], $groups);
    $payload = $processPayload($service, $ref, $row, $local, $categoriesRepo, $unitsRepo, $pharmaFormsRepo, $defaultUnitId);
    $diffs = $diffFields($local, $payload);
    $hash = $computeHash->invoke($service, $row);
    $storedHash = (string)($local['sap_item_hash'] ?? '');
    $hashMatch = $storedHash !== '' && hash_equals($storedHash, $hash);
    $sapDiffers = $differsLocal->invoke($service, $row, $local, $pharmaFormsRepo);

    if ($diffs !== []) {
        $wouldUpdate++;
        foreach (array_keys($diffs) as $f) {
            $fieldCounts[$f] = ($fieldCounts[$f] ?? 0) + 1;
        }
        if (count($examples) < 8) {
            $examples[] = [
                'erp' => $erp,
                'hash_match' => $hashMatch,
                'sap_differs' => $sapDiffers,
                'diffs' => $diffs,
                'stored_hash' => substr($storedHash, 0, 12),
                'new_hash' => substr($hash, 0, 12),
            ];
        }
    }
}

echo "=== Diagnóstico diff sync SAP (amostra {$examined} itens) ===\n\n";
echo "Com diferença payload vs local: {$wouldUpdate}\n";
echo "Sem diferença: " . ($examined - $wouldUpdate) . "\n\n";

if ($fieldCounts !== []) {
    arsort($fieldCounts);
    echo "Campos que mais divergem:\n";
    foreach ($fieldCounts as $field => $count) {
        echo sprintf("  %-22s %d\n", $field . ':', $count);
    }
    echo "\nExemplos:\n";
    foreach ($examples as $ex) {
        echo "\n  ERP {$ex['erp']} | hash_match=" . ($ex['hash_match'] ? 'sim' : 'não')
            . ' | sap_differs=' . ($ex['sap_differs'] ? 'sim' : 'não') . "\n";
        foreach ($ex['diffs'] as $field => $vals) {
            $l = json_encode($vals['local'], JSON_UNESCAPED_UNICODE);
            $p = json_encode($vals['payload'], JSON_UNESCAPED_UNICODE);
            echo "    {$field}: local={$l} payload={$p}\n";
        }
    }
}
