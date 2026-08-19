<?php
declare(strict_types=1);

/**
 * Sincronização SAP/BEAS → MySQL do Dashboard de Produção.
 *
 * Uso:
 *   php scripts/sync_prod_production_sap.php                # incremental
 *   php scripts/sync_prod_production_sap.php --full         # últimos 24 meses + OPs abertas
 *   php scripts/sync_prod_production_sap.php --today        # só o dia corrente
 *   php scripts/sync_prod_production_sap.php --incremental
 *
 * No Portal:
 *   - 1º acesso do dia ao dashboard dispara incremental automaticamente
 *   - botão "Atualizar agora" = só incremental (nunca full)
 *
 * Cron opcional (madrugada):
 *   45 2 * * * cd /path/to/administrativo && php scripts/sync_prod_production_sap.php >> storage/logs/prod_production_sync.log 2>&1
 */
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

use App\adms\Models\Services\ProdProductionSapSyncService;

$mode = 'incremental';
foreach ($argv ?? [] as $arg) {
    $arg = rtrim((string) $arg, '.,');
    if ($arg === '--full') {
        $mode = 'full';
    } elseif ($arg === '--today') {
        $mode = 'today';
    } elseif ($arg === '--incremental') {
        $mode = 'incremental';
    }
}

echo '[' . date('Y-m-d H:i:s') . "] Produção SAP/BEAS sync ({$mode})...\n";

try {
    $service = new ProdProductionSapSyncService();
    $result = $service->sync($mode);
    echo ($result['success'] ? 'OK' : 'FALHA') . ': ' . ($result['message'] ?? '') . "\n";
    echo sprintf(
        "Fonte: %s | Fetch: %d | Upsert: %d | Período: %s → %s\n",
        $result['source'] ?? '-',
        $result['rows_fetched'] ?? 0,
        $result['rows_upserted'] ?? 0,
        $result['date_from'] ?? '-',
        $result['date_to'] ?? '-'
    );
    if (!empty($result['beas_tables'])) {
        echo 'Tabelas BEAS detectadas: ' . $result['beas_tables'] . "\n";
    }
    exit(!empty($result['success']) ? 0 : 1);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . "\n");
    exit(1);
}
