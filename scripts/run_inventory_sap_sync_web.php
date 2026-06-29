<?php

declare(strict_types=1);

/**
 * Executa sincronização SAP iniciada pela interface web (processo CLI separado).
 * MySQL: .env | SAP: adms_sap_api_config (Configuração SAP API).
 *
 * Uso:
 *   php scripts/run_inventory_sap_sync_web.php --run-id=123 --items
 *   php scripts/run_inventory_sap_sync_web.php --run-id=124 --structures
 */
require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvInventorySapSyncRunsRepository;
use App\adms\Models\Services\InventorySapSyncService;

$runId = 0;
$type = 'items';
$full = false;
$code = null;
$group = null;
$autoContinue = true;

foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--run-id=')) {
        $runId = (int) substr($arg, 9);
    } elseif ($arg === '--structures') {
        $type = 'structures';
    } elseif ($arg === '--items') {
        $type = 'items';
    } elseif ($arg === '--full') {
        $full = true;
    } elseif ($arg === '--no-auto-continue') {
        $autoContinue = false;
    } elseif (str_starts_with($arg, '--code=')) {
        $code = trim(substr($arg, 7));
    } elseif (str_starts_with($arg, '--group=')) {
        $group = trim(substr($arg, 8));
    }
}

if ($runId <= 0) {
    fwrite(STDERR, "Parâmetro --run-id é obrigatório.\n");
    exit(1);
}

$dbName = trim((string)($_ENV['DB_NAME'] ?? $_ENV['DB_DATABASE'] ?? ''));
if ($dbName === '' || !isset($_ENV['DB_HOST'], $_ENV['DB_USER'])) {
    $message = 'Credenciais MySQL ausentes no .env (DB_HOST, DB_NAME, DB_USER).';
    fwrite(STDERR, $message . PHP_EOL);
    (new InvInventorySapSyncRunsRepository())->update($runId, [
        'status' => 'failed',
        'finished_at' => date('Y-m-d H:i:s'),
        'result_message' => $message,
        'error_log' => $message,
        'progress_label' => 'Falha ao iniciar',
    ]);
    exit(1);
}

@set_time_limit(0);

try {
    $service = new InventorySapSyncService();
    if ($type === 'structures') {
        $result = $service->syncAllItemStructures($runId);
    } else {
        $result = $service->syncItemsAndCostsInteractive(
            $full,
            ($code ?? '') !== '' ? $code : null,
            ($group ?? '') !== '' ? $group : null,
            $autoContinue,
            40,
            $runId
        );
    }

    exit(!empty($result['success']) ? 0 : 1);
} catch (Throwable $e) {
    $message = 'Falha na sincronização SAP: ' . $e->getMessage();
    fwrite(STDERR, $message . PHP_EOL);
    (new InvInventorySapSyncRunsRepository())->update($runId, [
        'status' => 'failed',
        'finished_at' => date('Y-m-d H:i:s'),
        'result_message' => $message,
        'error_log' => $e->getMessage(),
        'progress_label' => 'Falha',
    ]);
    exit(1);
}
