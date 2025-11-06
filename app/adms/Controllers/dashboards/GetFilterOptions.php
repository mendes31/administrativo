<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Models\Repository\DashboardsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;

/**
 * API para buscar opções de filtros (valores distintos)
 */
class GetFilterOptions
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $dashboardId = (int)($_GET['dashboard_id'] ?? 0);
            $fieldName = $_GET['field'] ?? '';
            $userId = $_SESSION['user_id'] ?? 0;
            
            if (!$dashboardId || !$fieldName) {
                throw new \Exception('Parâmetros inválidos');
            }
            
            $repo = new DashboardsRepository();
            
            // Verificar acesso
            if (!$repo->canAccess($dashboardId, $userId)) {
                throw new \Exception('Sem permissão para acessar este dashboard');
            }
            
            $dashboard = $repo->getById($dashboardId);
            
            if (!$dashboard) {
                throw new \Exception('Dashboard não encontrado');
            }
            
            // Verificar se há um relatório de filtro específico configurado
            $filtersConfig = $dashboard['filters_config'] ?? [];
            $filterReportId = null;
            $sourceField = $fieldName;
            
            foreach ($filtersConfig as $filterConfig) {
                if (($filterConfig['field'] ?? '') === $fieldName) {
                    $filterReportId = $filterConfig['filter_report_id'] ?? null;
                    $sourceField = $filterConfig['source_field'] ?? $fieldName;
                    break;
                }
            }
            
            // Se houver relatório de filtro específico, usar ele
            if ($filterReportId) {
                $filterReport = $repo->getById($filterReportId);
                if ($filterReport) {
                    $sql = $filterReport['custom_sql'];
                    $fieldName = $sourceField; // Usar nome do campo do relatório de filtro
                    error_log("🔍 Usando relatório de filtro específico: {$filterReport['name']} (campo: {$fieldName})");
                } else {
                    // Relatório de filtro não encontrado, usar query original
                    $sql = $dashboard['custom_sql'];
                }
            } else {
                // Executar query original (limitada) e extrair valores únicos no PHP
                $sql = $dashboard['custom_sql'];
            }
            
            // Adicionar TOP 2000 para limitar resultados (sintaxe HANA)
            if (!preg_match('/\bTOP\s+\d+/i', $sql) && !preg_match('/\bLIMIT\s+\d+/i', $sql)) {
                // Adicionar TOP após SELECT
                $sql = preg_replace('/\bSELECT\b/i', 'SELECT TOP 2000', $sql, 1);
            }
            
            $queryBuilder = new DynamicQueryBuilderService();
            $result = $queryBuilder->executeReport([
                'custom_sql' => $sql,
                'query_mode' => 'custom_sql'
            ]);
            
            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Erro ao buscar opções');
            }
            
            // Extrair valores distintos do campo específico
            $uniqueValues = [];
            foreach ($result['data'] as $row) {
                $value = $row[$fieldName] ?? null;
                if ($value !== null && $value !== '' && !isset($uniqueValues[$value])) {
                    $uniqueValues[$value] = true;
                }
            }
            
            // Converter para array de opções
            $options = [];
            foreach (array_keys($uniqueValues) as $value) {
                $options[] = ['value' => $value, 'label' => $value];
            }
            
            // Ordenar
            usort($options, function($a, $b) {
                return strcmp($a['label'], $b['label']);
            });
            
            echo json_encode([
                'success' => true,
                'options' => $options,
                'count' => count($options)
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
}

