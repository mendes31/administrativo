<?php

declare(strict_types=1);

namespace App\adms\Controllers\cashFlow;

use App\adms\Models\Services\FinCashFlowDashboardService;
use App\adms\Models\Services\FinCashFlowSapSyncService;
use Exception;
use Throwable;

class FinCashFlowDashboardData
{
    public function index(): void
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = $this->readInput();
            $action = (string) ($input['action'] ?? 'dashboard');

            if ($action === 'drill') {
                $service = new FinCashFlowDashboardService();
                echo json_encode($service->getDrillDown($input), JSON_UNESCAPED_UNICODE);
                exit;
            }

            $skipAuto = !empty($input['skip_auto_sync']);
            $autoSync = null;
            if (!$skipAuto) {
                try {
                    $syncService = new FinCashFlowSapSyncService();
                    $autoSync = $syncService->syncIfStaleDaily();
                } catch (Throwable $e) {
                    $autoSync = [
                        'ran' => false,
                        'skipped' => true,
                        'reason' => 'error',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $service = new FinCashFlowDashboardService();
            $payload = $service->getDashboardData($input);
            $payload['auto_sync'] = $autoSync;

            if (is_array($autoSync) && !empty($autoSync['ran']) && !empty($autoSync['result']['success'])) {
                $payload['warning'] = (string) ($autoSync['result']['message'] ?? 'Sincronização diária automática concluída.');
            } elseif (is_array($autoSync) && ($autoSync['reason'] ?? '') === 'error') {
                $payload['warning'] = 'Falha no sync automático: ' . ($autoSync['error'] ?? 'erro desconhecido')
                    . '. Exibindo cache anterior.';
            }

            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    private function readInput(): array
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            if (stripos($contentType, 'application/json') !== false) {
                $raw = file_get_contents('php://input');
                $decoded = json_decode($raw ?: '{}', true);
                return is_array($decoded) ? $decoded : [];
            }
            return $_POST;
        }
        return $_GET;
    }
}
