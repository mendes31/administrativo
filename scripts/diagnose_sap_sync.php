<?php

declare(strict_types=1);

/**
 * Diagnóstico da sincronização SAP — usa adms_sap_api_config (mesma fonte da tela Configuração SAP API).
 *
 * Uso: php scripts/diagnose_sap_sync.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

use App\adms\Models\Repository\AdmsSapApiConfigRepository;
use App\adms\Models\Services\InventorySapSyncService;
use App\adms\Models\Services\SapReportApiService;

echo "=== Diagnóstico sync SAP (config do sistema) ===\n\n";

$config = (new AdmsSapApiConfigRepository())->getConfig();
if (empty($config) || (int)($config['is_active'] ?? 0) !== 1) {
    echo "Integração inativa ou não configurada. Acesse Configurações → Configuração SAP API.\n";
    exit(1);
}

echo 'base_url: ' . ($config['base_url'] ?? '—') . "\n";
echo 'page_size: ' . ($config['page_size'] ?? '—') . "\n";
echo 'timeout_ms: ' . ($config['timeout_ms'] ?? '—') . "\n\n";

try {
    $sap = new SapReportApiService();
} catch (Throwable $e) {
    echo 'Falha ao instanciar SapReportApiService: ' . $e->getMessage() . "\n";
    exit(1);
}

foreach ([
    'ping' => 'SELECT TOP 1 T0."ItemCode" FROM OITM T0',
    'items_light' => 'SELECT TOP 5 T0."ItemCode", T0."ItemName" FROM OITM T0 WHERE T0."frozenFor" = \'N\'',
] as $label => $sql) {
    echo "--- {$label} ---\n";
    try {
        $result = $sap->execute($sql);
        echo 'OK: ' . count($result['data'] ?? []) . " linha(s)\n\n";
    } catch (Throwable $e) {
        echo 'ERRO: ' . $e->getMessage() . "\n\n";
    }
}

echo "--- batch OITW join ---\n";
foreach ([50, 100, 200, 500] as $n) {
    $sql = 'SELECT TOP ' . $n . ' T0."ItemCode", T0."ItemName", '
        . 'TO_DECIMAL(COALESCE(W."AvgPrice", T0."AvgPrice"), 19, 4) AS "AvgPrice" '
        . 'FROM OITM T0 '
        . 'LEFT JOIN OITW W ON W."ItemCode" = T0."ItemCode" '
        . 'AND W."WhsCode" = CASE WHEN T0."DfltWH" = \'TJQP\' THEN \'TJQR\' '
        . 'WHEN T0."DfltWH" = \'APQP\' THEN \'APQR\' ELSE T0."DfltWH" END '
        . 'WHERE T0."frozenFor" = \'N\' ORDER BY T0."ItemCode"';
    try {
        $r = $sap->execute($sql);
        echo "TOP {$n} OK: " . count($r['data'] ?? []) . "\n";
    } catch (Throwable $e) {
        echo "TOP {$n} ERRO: " . $e->getMessage() . "\n";
    }
}

$service = new InventorySapSyncService();
$ref = new ReflectionClass(InventorySapSyncService::class);

echo "\n--- query completa lote 1 (getSapItemsQueryBatch) ---\n";
$batchMethod = $ref->getMethod('getSapItemsQueryBatch');
$batchMethod->setAccessible(true);
$sqlBatch = $batchMethod->invoke($service, '', 200);
echo 'SQL length: ' . strlen($sqlBatch) . "\n";
try {
    $r = $sap->execute($sqlBatch);
    $rows1 = $r['data'] ?? [];
    echo 'OK lote 1: ' . count($rows1) . " linha(s)\n";
    if ($rows1 !== []) {
        $last = '';
        foreach ($rows1 as $row) {
            $c = trim((string)($row['ItemCode'] ?? ''));
            if ($c !== '' && ($last === '' || strcmp($c, $last) > 0)) {
                $last = $c;
            }
        }
        echo "Último código lote 1: {$last}\n";
        $sqlBatch2 = $batchMethod->invoke($service, $last, 200);
        try {
            $r2 = $sap->execute($sqlBatch2);
            echo 'OK lote 2: ' . count($r2['data'] ?? []) . " linha(s)\n";
        } catch (Throwable $e2) {
            echo 'ERRO lote 2: ' . $e2->getMessage() . "\n";
        }
    }
} catch (Throwable $e) {
    echo 'ERRO lote 1: ' . $e->getMessage() . "\n";
}

echo "\n--- sync cadastro (lotes, sem OINM) ---\n";
$method = $ref->getMethod('fetchAllSapItemRows');
$method->setAccessible(true);
try {
    $start = microtime(true);
    $rows = $method->invoke($service, $sap);
    $elapsed = round(microtime(true) - $start, 2);
    echo 'OK: ' . count($rows) . " item(ns) em {$elapsed}s\n";
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . "\n";
    exit(1);
}
