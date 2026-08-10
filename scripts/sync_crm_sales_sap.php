<?php
declare(strict_types=1);

/**
 * Sincronização SAP → MySQL do Dashboard de Vendas CRM.
 *
 * Uso:
 *   php scripts/sync_crm_sales_sap.php                # incremental (cron opcional)
 *   php scripts/sync_crm_sales_sap.php --full         # últimos 36 meses (1ª vez)
 *   php scripts/sync_crm_sales_sap.php --today        # só o dia corrente
 *   php scripts/sync_crm_sales_sap.php --incremental  # explícito
 *
 * No Portal:
 *   - 1º acesso do dia ao dashboard dispara incremental automaticamente
 *   - botão "Atualizar agora" = só incremental (nunca full)
 *
 * Cron opcional (madrugada), se preferir não depender do 1º acesso:
 *   30 2 * * * cd /path/to/administrativo && php scripts/sync_crm_sales_sap.php >> storage/logs/crm_sales_sync.log 2>&1
 */
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

use App\adms\Models\Services\CrmSalesSapSyncService;

$mode = 'incremental';
foreach ($argv ?? [] as $arg) {
    if ($arg === '--full') {
        $mode = 'full';
    } elseif ($arg === '--today') {
        $mode = 'today';
    } elseif ($arg === '--incremental') {
        $mode = 'incremental';
    }
}

echo '[' . date('Y-m-d H:i:s') . "] CRM Sales SAP sync ({$mode})...\n";

try {
    $service = new CrmSalesSapSyncService();
    $result = $service->sync($mode);
    echo ($result['success'] ? 'OK' : 'FALHA') . ': ' . ($result['message'] ?? '') . "\n";
    echo sprintf(
        "Fonte: %s | Fetch: %d | Upsert: %d | Meses: %d | Período: %s → %s\n",
        $result['source'] ?? '-',
        $result['rows_fetched'] ?? 0,
        $result['rows_upserted'] ?? 0,
        $result['months_processed'] ?? 0,
        $result['date_from'] ?? '-',
        $result['date_to'] ?? '-'
    );
    exit(!empty($result['success']) ? 0 : 1);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . "\n");
    exit(1);
}
