<?php

namespace App\adms\Controllers\reports;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;

class ExecuteDynamicReport
{
    public function index(): void
    {
        // LOG USANDO error_log() QUE SEMPRE FUNCIONA
        error_log("===========================================");
        error_log("🚀 ExecuteDynamicReport::index() - MÉTODO CHAMADO");
        error_log("📍 __DIR__: " . __DIR__);
        error_log("📍 REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
        error_log("📍 POST data: " . json_encode($_POST));
        error_log("===========================================");
        
        // Sem limite de memória (queries do SAP podem retornar muitos dados - todas as vendas, etc)
        ini_set('memory_limit', '-1');
        
        // Aumentar tempo de execução para 10 minutos (queries grandes podem demorar)
        ini_set('max_execution_time', '600');
        
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
            error_log("📊 ExecuteDynamicReport - Verificando REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                error_log("❌ ExecuteDynamicReport - Método inválido");
                throw new \Exception('Método inválido - use POST');
            }
            
            $reportId = $_POST['report_id'] ?? null;
            $queryMode = $_POST['query_mode'] ?? 'builder';
            $forceRefresh = !empty($_POST['force_refresh']);
            
            error_log("📊 ExecuteDynamicReport - report_id: {$reportId}, query_mode: {$queryMode}, force_refresh: " . ($forceRefresh ? 'true' : 'false'));
            
            // Validar CSRF token apenas para salvamento (não para previews)
            // Previews podem ser chamados múltiplas vezes e o token seria invalidado
            $isPreview = empty($reportId) || $reportId === 'preview';
            
            if (!$isPreview) {
                // Apenas validar CSRF para salvamento de relatórios
                $csrfToken = $_POST['csrf_token'] ?? '';
                error_log("📊 ExecuteDynamicReport - Validando CSRF (não é preview)");
                
                if (!empty($csrfToken)) {
                    if (!CSRFHelper::validateCSRFToken('form_dynamic_report', $csrfToken)) {
                        error_log("❌ ExecuteDynamicReport - CSRF token inválido");
                        throw new \Exception('Token de segurança inválido. Recarregue a página e tente novamente.');
                    }
                    error_log("✅ ExecuteDynamicReport - CSRF token válido");
                } else {
                    error_log("⚠️ ExecuteDynamicReport - CSRF token não recebido para salvamento");
                }
            } else {
                error_log("ℹ️ ExecuteDynamicReport - Modo preview, CSRF não obrigatório");
            }
            
            // Modo preview: criar relatório temporário a partir dos dados POST
            if ($isPreview) {
                $cacheNamespace = 'preview_' . ($_SESSION['user_id'] ?? 'guest');
                if ($queryMode === 'custom_sql') {
                    // SQL Personalizado
                    $customSql = $_POST['custom_sql'] ?? '';
                    
                    error_log("📝 SQL Recebido do POST (raw): " . bin2hex(substr($customSql, 0, 50)));
                    error_log("📝 SQL Recebido do POST (string): [" . $customSql . "]");
                    error_log("📝 SQL Length: " . strlen($customSql));
                    error_log("📝 SQL após trim: [" . trim($customSql) . "]");
                    
                    $report = [
                        'custom_sql' => $customSql,
                        'query_mode' => 'custom_sql',
                        'visualization_type' => $_POST['visualization_type'] ?? 'table',
                        'cache_namespace' => $cacheNamespace,
                        'force_refresh' => $forceRefresh
                    ];
                    
                    error_log("📝 SQL no report: " . substr($report['custom_sql'] ?? '', 0, 200));
                } else {
                    // Builder
                    $report = [
                        'data_source' => $_POST['data_source'] ?? '',
                        'fields' => json_decode($_POST['fields'] ?? '[]', true),
                        'filters' => json_decode($_POST['filters'] ?? '[]', true),
                        'groupby' => json_decode($_POST['groupby'] ?? '[]', true),
                        'orderby' => json_decode($_POST['orderby'] ?? '[]', true),
                        'query_mode' => 'builder',
                        'visualization_type' => $_POST['visualization_type'] ?? 'table',
                        'cache_namespace' => $cacheNamespace,
                        'force_refresh' => $forceRefresh
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

                $report['cache_namespace'] = 'report_' . $reportId;
                $report['force_refresh'] = $forceRefresh;
            }
            
            error_log("📊 ExecuteDynamicReport - Antes de chamar queryBuilder");
            error_log("📊 ExecuteDynamicReport - Report: " . json_encode($report, JSON_UNESCAPED_UNICODE));
            
            $queryBuilder = new DynamicQueryBuilderService();
            $result = $queryBuilder->executeReport($report);
            
            error_log("📊 ExecuteDynamicReport - Resultado: " . ($result['success'] ? 'SUCESSO' : 'ERRO'));
            
            // Só registrar execução se não for preview
            if ($result['success'] && !empty($reportId) && $reportId !== 'preview') {
                $repo = $repo ?? new DynamicReportsRepository();
                $executionTime = (float)($result['execution_time'] ?? 0.0);
                $rowsCount = (int)($result['rows_count'] ?? 0);
                $repo->logExecution((int)$reportId, $_SESSION['user_id'] ?? 0, $executionTime, $rowsCount);
            }
            
            // Limpar buffer e enviar apenas JSON
            ob_clean();
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
        } catch (\Throwable $e) {
            // Capturar QUALQUER erro (incluindo Error e Exception)
            error_log("❌ ERRO CAPTURADO: " . $e->getMessage());
            error_log("❌ Arquivo: " . $e->getFile() . ":" . $e->getLine());
            error_log("❌ Trace: " . substr($e->getTraceAsString(), 0, 1000));
            
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

