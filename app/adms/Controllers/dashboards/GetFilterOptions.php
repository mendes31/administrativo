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
    private function quoteIdentifier(string $field): string
    {
        $trimmed = trim($field);

        // Evitar dupla citação se já existir
        if (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"')) {
            return $trimmed;
        }

        // Campos com funções ou pontos devem ser tratados parcialmente
        if (str_contains($trimmed, '.')) {
            $parts = array_map(fn($part) => $part === '*' ? '*' : '"' . str_replace('"', '', trim($part)) . '"', explode('.', $trimmed));
            return implode('.', $parts);
        }

        return '"' . str_replace('"', '', $trimmed) . '"';
    }

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

                if (!$filterReport || empty($filterReport['custom_sql'])) {
                    error_log("Filtro {$filterLabel}: relatório {$filterReportId} não encontrado ou sem SQL.");
                    echo json_encode(['success' => false, 'error' => 'Relatório de filtro não encontrado.']);
                    return;
                }

                $sql = $filterReport['custom_sql'];

                // Limitar apenas aos campos necessários (value/label) para evitar consumo excessivo de memória
                if (!empty($filterReport['source_field'])) {
                    $valueField = $filterReport['source_field'];
                    $labelField = $filterReport['display_field'] ?? $filterReport['source_field'];
                    $sql = "SELECT {$valueField} as value, {$labelField} as label FROM ( {$sql} ) AS src";
                }

                $queryBuilder = new DynamicQueryBuilderService();
                $response = $queryBuilder->executeReport([
                    'custom_sql' => $sql,
                    'query_mode' => 'custom_sql',
                    'disable_limit' => true
                ]);
                
                if (($response['success'] ?? false) && !empty($response['data'])) {
                    $options = [];
                    foreach ($response['data'] as $row) {
                        if (isset($row['value']) || isset($row['label'])) {
                            $options[] = [
                                'value' => $row['value'] ?? $row['label'],
                                'label' => $row['label'] ?? $row['value'] ?? ''
                            ];
                        } elseif (isset($row[$sourceField])) {
                            $options[] = [
                                'value' => $row[$sourceField],
                                'label' => $row[$sourceField]
                            ];
                        }
                    }

                    $options = array_values(array_unique($options, SORT_REGULAR));
                    usort($options, fn($a, $b) => strcmp($a['label'], $b['label']));

                    echo json_encode([
                        'success' => true,
                        'options' => $options,
                        'count' => count($options)
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                // Caso tenha ocorrido erro ou não existam dados
                $errorMessage = $response['error'] ?? 'Nenhuma opção encontrada para este filtro.';
                echo json_encode([
                    'success' => false,
                    'error' => $errorMessage,
                    'options' => [],
                    'count' => 0
                ], JSON_UNESCAPED_UNICODE);
                
            } else {
                error_log("   ℹ️ Nenhum filter_report_id configurado, usando query do dashboard");
                $sql = $dashboard['custom_sql'];
                
                // Para relatórios de filtro (vendedores, grupos), não limitar
                // Para query principal, limitar a 5000 registros
                $useLimit = false; // Não usar limit para relatórios de filtro
                
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
            }
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
}

