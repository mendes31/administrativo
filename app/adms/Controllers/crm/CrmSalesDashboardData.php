<?php

declare(strict_types=1);

namespace App\adms\Controllers\crm;

use App\adms\Models\Services\CrmSalesDashboardService;
use App\adms\Models\Services\CrmSalesSapSyncService;
use Exception;
use Throwable;

/**
 * API JSON agregada do Dashboard de Vendas CRM (cache MySQL).
 *
 * No 1º acesso do dia (ou cache vazio), dispara sync incremental antes de agregar.
 */
class CrmSalesDashboardData
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
                    $syncService = new CrmSalesSapSyncService();
                    $autoSync = $syncService->syncIfStaleDaily();
                } catch (Throwable $e) {
                    // Não bloqueia o painel: devolve cache atual + aviso
                    $autoSync = [
                        'ran' => false,
                        'skipped' => true,
                        'reason' => 'error',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $filters = [
                'periodo' => (string) ($input['periodo'] ?? '12'),
                'date_from' => trim((string) ($input['date_from'] ?? '')),
                'date_to' => trim((string) ($input['date_to'] ?? '')),
                'vendedor' => $input['vendedor'] ?? null,
                'grupo_cliente' => $input['grupo_cliente'] ?? null,
                'regiao' => $input['regiao'] ?? null,
                'grupo_item' => $input['grupo_item'] ?? null,
                'ano_mes' => $input['ano_mes'] ?? null,
            ];

            $service = new CrmSalesDashboardService();
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
