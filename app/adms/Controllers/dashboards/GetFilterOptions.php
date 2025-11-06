<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Models\Repository\DashboardsRepository;
use App\adms\Models\Repository\DynamicReportsRepository;
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
            
            // Verificar se há configuração de filtro
            $repo = new DashboardsRepository();
            
            // Verificar acesso
            if (!$repo->canAccess($dashboardId, $userId)) {
                throw new \Exception('Sem permissão para acessar este dashboard');
            }
            
            $dashboard = $repo->getById($dashboardId);
            
            if (!$dashboard) {
                throw new \Exception('Dashboard não encontrado');
            }
            
            $filtersConfig = $dashboard['filters_config'] ?? [];
            $filterLabel = '';
            
            // Buscar configuração do filtro atual
            foreach ($filtersConfig as $filterConfig) {
                if (($filterConfig['field'] ?? '') === $fieldName) {
                    $filterLabel = $filterConfig['label'] ?? '';
                    break;
                }
            }
            
            // Para filtro de MÊS, retornar lista fixa (não buscar do banco)
            if (stripos($filterLabel, 'mês') !== false || stripos($filterLabel, 'mes') !== false || stripos($filterLabel, 'month') !== false) {
                $months = [
                    ['value' => '01', 'label' => 'Janeiro'],
                    ['value' => '02', 'label' => 'Fevereiro'],
                    ['value' => '03', 'label' => 'Março'],
                    ['value' => '04', 'label' => 'Abril'],
                    ['value' => '05', 'label' => 'Maio'],
                    ['value' => '06', 'label' => 'Junho'],
                    ['value' => '07', 'label' => 'Julho'],
                    ['value' => '08', 'label' => 'Agosto'],
                    ['value' => '09', 'label' => 'Setembro'],
                    ['value' => '10', 'label' => 'Outubro'],
                    ['value' => '11', 'label' => 'Novembro'],
                    ['value' => '12', 'label' => 'Dezembro']
                ];
                
                echo json_encode([
                    'success' => true,
                    'options' => $months,
                    'count' => count($months)
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Para filtro de ANO, não deve buscar do banco (será input text)
            if (stripos($filterLabel, 'ano') !== false || stripos($filterLabel, 'year') !== false) {
                echo json_encode([
                    'success' => true,
                    'options' => [],
                    'count' => 0,
                    'message' => 'Filtro de ano é um campo digitável'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Verificar se há um relatório de filtro específico configurado
            $filterReportId = null;
            $sourceField = $fieldName;
            
            error_log("========================================");
            error_log("🔍 GetFilterOptions - Iniciando busca");
            error_log("   Dashboard ID: {$dashboardId}");
            error_log("   Campo solicitado: {$fieldName}");
            error_log("   Total de filtros configurados: " . count($filtersConfig));
            
            foreach ($filtersConfig as $filterConfig) {
                error_log("   - Filtro: " . ($filterConfig['label'] ?? 'SEM LABEL') . " | Campo: " . ($filterConfig['field'] ?? 'SEM CAMPO') . " | filter_report_id: " . ($filterConfig['filter_report_id'] ?? 'NULL'));
                
                if (($filterConfig['field'] ?? '') === $fieldName) {
                    $filterReportId = $filterConfig['filter_report_id'] ?? null;
                    $sourceField = $filterConfig['source_field'] ?? $fieldName;
                    error_log("   ✅ MATCH ENCONTRADO!");
                    error_log("      filter_report_id: " . ($filterReportId ?? 'NULL'));
                    error_log("      source_field: {$sourceField}");
                    break;
                }
            }
            
            // Se houver relatório de filtro específico, usar ele
            if ($filterReportId) {
                error_log("   🎯 Buscando relatório de filtro ID: {$filterReportId}");
                
                // CORREÇÃO: Usar DynamicReportsRepository em vez de DashboardsRepository
                $reportsRepo = new DynamicReportsRepository();
                $filterReport = $reportsRepo->getById($filterReportId);
                
                if ($filterReport) {
                    $sql = $filterReport['custom_sql'];
                    $fieldName = $sourceField; // Usar nome do campo do relatório de filtro
                    error_log("   ✅ Relatório de filtro encontrado: {$filterReport['name']}");
                    error_log("   ✅ SQL: " . substr($sql, 0, 200) . "...");
                    error_log("   ✅ Campo a extrair: {$fieldName}");
                } else {
                    error_log("   ❌ Relatório de filtro {$filterReportId} NÃO ENCONTRADO! Usando query original");
                    $sql = $dashboard['custom_sql'];
                }
            } else {
                error_log("   ℹ️ Nenhum filter_report_id configurado, usando query do dashboard");
                $sql = $dashboard['custom_sql'];
            }
            error_log("========================================");
            
            // Para relatórios de filtro (vendedores, grupos), não limitar
            // Para query principal, limitar a 5000 registros
            $useLimit = !$filterReportId;
            
            if ($useLimit && !preg_match('/\bTOP\s+\d+/i', $sql) && !preg_match('/\bLIMIT\s+\d+/i', $sql)) {
                // Adicionar TOP 5000 após SELECT para query principal
                $sql = preg_replace('/\bSELECT\b/i', 'SELECT TOP 5000', $sql, 1);
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
            
            // Ordenar alfabeticamente
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

