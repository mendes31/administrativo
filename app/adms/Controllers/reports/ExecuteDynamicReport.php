<?php

namespace App\adms\Controllers\reports;

use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;

class ExecuteDynamicReport
{
    public function index(): void
    {
        // Limpar TODOS os buffers de output
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Iniciar novo buffer
        ob_start();
        
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método inválido']);
            exit;
        }
        
        $reportId = $_POST['report_id'] ?? null;
        $queryMode = $_POST['query_mode'] ?? 'builder';
        
        // Log de debug
        error_log("📊 ExecuteDynamicReport - report_id: {$reportId}, query_mode: {$queryMode}");
        
        // Modo preview: criar relatório temporário a partir dos dados POST
        if (empty($reportId) || $reportId === 'preview') {
            if ($queryMode === 'custom_sql') {
                // SQL Personalizado
                $report = [
                    'custom_sql' => $_POST['custom_sql'] ?? '',
                    'query_mode' => 'custom_sql',
                    'visualization_type' => $_POST['visualization_type'] ?? 'table'
                ];
                error_log("📝 SQL Personalizado: " . ($report['custom_sql'] ?? 'vazio'));
            } else {
                // Builder
                $report = [
                    'data_source' => $_POST['data_source'] ?? '',
                    'fields' => json_decode($_POST['fields'] ?? '[]', true),
                    'filters' => json_decode($_POST['filters'] ?? '[]', true),
                    'groupby' => json_decode($_POST['groupby'] ?? '[]', true),
                    'orderby' => json_decode($_POST['orderby'] ?? '[]', true),
                    'query_mode' => 'builder',
                    'visualization_type' => $_POST['visualization_type'] ?? 'table'
                ];
                error_log("🔨 Builder - data_source: " . ($report['data_source'] ?? 'vazio'));
            }
        } else {
            // Modo normal: buscar relatório salvo
            $repo = new DynamicReportsRepository();
            $report = $repo->getById((int)$reportId);
            
            if (!$report) {
                echo json_encode(['success' => false, 'error' => 'Relatório não encontrado']);
                exit;
            }
        }
        
        try {
            $queryBuilder = new DynamicQueryBuilderService();
            $result = $queryBuilder->executeReport($report);
            
            // Só registrar execução se não for preview
            if ($result['success'] && !empty($reportId) && $reportId !== 'preview') {
                $repo = $repo ?? new DynamicReportsRepository();
                $repo->logExecution((int)$reportId, $_SESSION['user_id'] ?? 0,
                    $result['execution_time'], $result['rows_count']);
            }
            
            // Limpar buffer e enviar apenas JSON
            ob_clean();
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            // Capturar qualquer erro e retornar como JSON
            ob_clean();
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], JSON_UNESCAPED_UNICODE);
        }
        
        ob_end_flush();
        exit;
    }
}

