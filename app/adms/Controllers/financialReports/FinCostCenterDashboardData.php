<?php

declare(strict_types=1);

namespace App\adms\Controllers\financialReports;

use App\adms\Models\Services\FinCostCenterSapService;
use Throwable;

/**
 * JSON dos dashboards de centros de custo SAP.
 */
class FinCostCenterDashboardData
{
    public function index(): void
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = $_GET;
            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (stripos($contentType, 'application/json') !== false) {
                    $decoded = json_decode((string) file_get_contents('php://input'), true);
                    $input = is_array($decoded) ? $decoded : [];
                } else {
                    $input = $_POST;
                }
            }

            $service = new FinCostCenterSapService();
            $periodo = $service->resolvePeriod($input);
            $unidade = trim((string) ($input['unidade'] ?? ''));
            $centro = trim((string) ($input['centro'] ?? ''));
            $visao = (string) ($input['visao'] ?? 'dashboard');
            $includeDetails = $visao === 'lancamentos';

            $dash = $service->getDashboardData(
                $periodo['from'],
                $periodo['to'],
                $unidade,
                $centro,
                $includeDetails
            );

            $unidade = (string) ($dash['unidade'] ?? $unidade);
            $centro = (string) ($dash['centro'] ?? $centro);

            echo json_encode([
                'success' => true,
                'periodo' => $periodo,
                'unidade' => $unidade,
                'centro' => $centro,
                'visao' => $visao,
                'kpis' => $dash['kpis'],
                'unidades' => $dash['unidades'],
                'centros' => $dash['centros'],
                'tree' => $dash['tree'],
                'arvore' => $dash['arvore'],
                'chart' => $dash['chart'],
                'serie_mensal' => $dash['serie_mensal'],
                'stacked_unidades' => $dash['stacked_unidades'],
                'ranking' => $dash['ranking'],
                'ranking_equipes' => $dash['ranking_equipes'] ?? [],
                'arvore_equipes' => $dash['arvore_equipes'] ?? [],
                'matrix' => $dash['matrix'],
                'lancamentos' => $dash['lancamentos'],
                'execution_time' => $dash['execution_time'],
                'rows_count' => $dash['rows_count'],
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
