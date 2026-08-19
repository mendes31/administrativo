<?php

declare(strict_types=1);

namespace App\adms\Controllers\production;

use App\adms\Models\Services\ProdProductionSapSyncService;
use Exception;

/**
 * Dispara sincronização do cache MySQL de produção SAP/BEAS.
 * Somente incremental na web (full só via CLI).
 */
class ProdProductionDashboardSync
{
    public function index(): void
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');
        header('Content-Type: application/json; charset=utf-8');

        try {
            $service = new ProdProductionSapSyncService();
            $result = $service->sync('incremental');

            echo json_encode([
                'success' => !empty($result['success']),
                'message' => $result['message'] ?? '',
                'sync' => $result,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }
}
