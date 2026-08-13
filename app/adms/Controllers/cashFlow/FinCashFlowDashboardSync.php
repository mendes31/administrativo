<?php

declare(strict_types=1);

namespace App\adms\Controllers\cashFlow;

use App\adms\Models\Services\FinCashFlowSapSyncService;
use Exception;

class FinCashFlowDashboardSync
{
    public function index(): void
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');
        header('Content-Type: application/json; charset=utf-8');

        try {
            $mode = 'incremental';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $raw = file_get_contents('php://input');
                $decoded = json_decode($raw ?: '{}', true);
                if (is_array($decoded) && ($decoded['mode'] ?? '') === 'accounts') {
                    $mode = 'accounts';
                }
            } elseif (($_GET['mode'] ?? '') === 'accounts') {
                $mode = 'accounts';
            }

            $service = new FinCashFlowSapSyncService();
            $result = $service->sync($mode);

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
