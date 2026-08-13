<?php
declare(strict_types=1);

/**
 * Sincronização SAP → MySQL do Dashboard de Fluxo de Caixa.
 *
 * Uso:
 *   php scripts/sync_fin_cash_flow_sap.php                # incremental
 *   php scripts/sync_fin_cash_flow_sap.php --full         # últimos 18 meses (1ª vez)
 *   php scripts/sync_fin_cash_flow_sap.php --today        # só o dia corrente
 *   php scripts/sync_fin_cash_flow_sap.php --accounts     # só cadastro de contas
 *
 * No Portal:
 *   - 1º acesso do dia ao dashboard dispara incremental automaticamente
 *   - botão "Sincronizar SAP" = incremental (nunca full)
 */
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

use App\adms\Models\Services\FinCashFlowSapSyncService;

$mode = 'incremental';
foreach ($argv ?? [] as $arg) {
    if ($arg === '--full') {
        $mode = 'full';
    } elseif ($arg === '--today') {
        $mode = 'today';
    } elseif ($arg === '--accounts') {
        $mode = 'accounts';
    } elseif ($arg === '--incremental') {
        $mode = 'incremental';
    }
}

echo '[' . date('Y-m-d H:i:s') . "] Fluxo de caixa SAP sync ({$mode})...\n";

try {
    $service = new FinCashFlowSapSyncService();
    $result = $service->sync($mode);
    echo ($result['message'] ?? 'Concluído.') . PHP_EOL;
    echo 'Contas: ' . (int) ($result['accounts_upserted'] ?? 0)
        . ' | Diário: ' . (int) ($result['daily_rows'] ?? 0)
        . ' | Previstos: ' . (int) ($result['forecast_rows'] ?? 0) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
