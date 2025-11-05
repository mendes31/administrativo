<?php

namespace App\adms\Controllers\reports;

use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;

class ExecuteDynamicReport
{
    public function index(): void
    {
        // Aumentar limite de memória para 512MB (queries do SAP podem retornar muitos dados)
        ini_set('memory_limit', '512M');
        
        // Aumentar tempo de execução para 120 segundos
        ini_set('max_execution_time', '120');
        
        // Registrar handler de erros fatais
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                // Limpar qualquer output anterior
                while (ob_get_level()) ob_end_clean();
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro fatal: ' . $error['message'],
                    'file' => basename($error['file'] ?? ''),
                    'line' => $error['line'] ?? 0,
                    'hint' => 'Se o erro persistir, adicione LIMIT na query ou reduza o período de datas'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        });
        
        // Limpar TODOS os buffers de output
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Iniciar novo buffer
        ob_start();
        
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método inválido - use POST');
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
                    error_log("📝 SQL Personalizado: " . substr($report['custom_sql'] ?? '', 0, 100) . '...');
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
                    throw new \Exception('Relatório não encontrado');
                }
            }
            
            $queryBuilder = new DynamicQueryBuilderService();
            $result = $queryBuilder->executeReport($report);
            
            // Só registrar execução se não for preview
            if ($result['success'] && !empty($reportId) && $reportId !== 'preview') {
                $repo = $repo ?? new DynamicReportsRepository();
                $repo->logExecution((int)$reportId, $_SESSION['user_id'] ?? 0,
                    $result['execution_time'] ?? 0, $result['rows_count'] ?? 0);
            }
            
            // Limpar buffer e enviar apenas JSON
            ob_clean();
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
        } catch (\Throwable $e) {
            // Capturar QUALQUER erro (incluindo Error e Exception)
            error_log("❌ Erro capturado: " . $e->getMessage());
            
            ob_clean();
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
        ob_end_flush();
        exit;
    }
}

