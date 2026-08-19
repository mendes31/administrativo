<?php

declare(strict_types=1);

namespace App\adms\Controllers\production;

use App\adms\Models\Services\ProdProductionDashboardService;
use App\adms\Models\Services\ProdProductionSapSyncService;
use Exception;
use Throwable;

/**
 * API JSON do Dashboard de Produção (cache MySQL).
 *
 * No 1º acesso do dia (ou cache vazio), dispara sync incremental antes de agregar.
 */
class ProdProductionDashboardData
{
    public function index(): void
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = $this->readInput();
            $skipAuto = !empty($input['skip_auto_sync']);

            $autoSync = null;
            if (!$skipAuto) {
                try {
                    $syncService = new ProdProductionSapSyncService();
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

            $filters = [
                'periodo' => (string) ($input['periodo'] ?? '30'),
                'date_from' => trim((string) ($input['date_from'] ?? '')),
                'date_to' => trim((string) ($input['date_to'] ?? '')),
                'linha' => $input['linha'] ?? null,
            ];

            $service = new ProdProductionDashboardService();
            $payload = $service->getDashboardData($filters);
            $payload['auto_sync'] = $autoSync;

            if (is_array($autoSync) && !empty($autoSync['ran']) && !empty($autoSync['result']['success'])) {
                $msg = (string) ($autoSync['result']['message'] ?? 'Sincronização diária automática concluída.');
                if (empty($payload['warning'])) {
                    $payload['warning'] = $msg;
                }
            } elseif (is_array($autoSync) && ($autoSync['reason'] ?? '') === 'error') {
                $payload['warning'] = 'Falha no sync automático do dia: ' . ($autoSync['error'] ?? 'erro desconhecido')
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
