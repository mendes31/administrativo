<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Models\Repository\DashboardsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;

/**
 * API para executar dashboard com filtros
 */
class ExecuteDashboard
{
    public function index(): void
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $dashboardId = (int)($_POST['dashboard_id'] ?? 0);
            $userId = $_SESSION['user_id'] ?? 0;
            $filters = $_POST['filters'] ?? [];
            
            $repo = new DashboardsRepository();
            
            // Verificar acesso
            if (!$repo->canAccess($dashboardId, $userId)) {
                throw new \Exception('Sem permissão para acessar este dashboard');
            }
            
            $dashboard = $repo->getById($dashboardId);
            
            if (!$dashboard) {
                throw new \Exception('Dashboard não encontrado');
            }
            
            // Aplicar filtros na query do relatório usando WHERE
            $sql = $this->applyFiltersWithWhere($dashboard['custom_sql'], $filters, $dashboard['filters_config']);
            
            // Executar query
            $queryBuilder = new DynamicQueryBuilderService();
            $result = $queryBuilder->executeReport([
                'custom_sql' => $sql,
                'query_mode' => 'custom_sql'
            ]);
            
            if ($result['success']) {
                // Calcular medidas antes dos KPIs
                $measures = $dashboard['measures_config'] ?? [];
                if (!empty($measures)) {
                    $result['measures'] = $this->calculateMeasures($result['data'], $measures);
                }
                
                // Calcular KPIs baseados na configuração (pode usar medidas)
                $result['kpis'] = $this->calculateKPIs($result['data'], $dashboard['kpis_config'], $result['measures'] ?? []);
                
                // Agregar dados para gráficos
                $result['chart_data'] = $this->aggregateForCharts($result['data'], $dashboard['charts_config']);
            }
            
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
    
    /**
     * Aplicar filtros adicionando AND na cláusula WHERE existente
     */
    private function applyFiltersWithWhere(string $sql, array $filters, array $filtersConfig): string
    {
        // Remover ponto e vírgula final se houver
        $sql = rtrim(trim($sql), ';');
        
        // Construir cláusulas WHERE
        $whereClauses = [];
        
        foreach ($filtersConfig as $filter) {
            $field = $filter['field'] ?? '';
            $type = $filter['type'] ?? 'text';
            
            if (!isset($filters[$field]) || $filters[$field] === '' || $filters[$field] === null) {
                continue;
            }
            
            $value = $filters[$field];
            
            // Aplicar filtro conforme tipo (adaptado para HANA SQL)
            switch ($type) {
                case 'year':
                    // Para HANA, usar YEAR(campo)
                    if (preg_match('/YEAR\s*\([^)]+\)/i', $field)) {
                        // Já tem YEAR na expressão do campo
                        $whereClauses[] = "{$field} = " . (int)$value;
                    } else {
                        // Campo sem YEAR - adicionar YEAR() para HANA
                        $whereClauses[] = "YEAR(T0.\"DataCriação\") = " . (int)$value;
                    }
                    break;
                    
                case 'month':
                    // Para HANA, usar MONTH(campo)
                    if (preg_match('/MONTH\s*\([^)]+\)/i', $field)) {
                        // Já tem MONTH na expressão do campo
                        $whereClauses[] = "{$field} = " . (int)$value;
                    } else {
                        // Campo sem MONTH - adicionar MONTH() para HANA
                        $whereClauses[] = "MONTH(T0.\"DataCriação\") = " . (int)$value;
                    }
                    break;
                    
                case 'number':
                    // Número direto
                    $whereClauses[] = "\"{$field}\" = " . (int)$value;
                    break;
                    
                case 'text':
                default:
                    // Texto - usar aspas simples e escape (sem alias, campo direto)
                    $escapedValue = str_replace("'", "''", $value);
                    $whereClauses[] = "\"{$field}\" = '{$escapedValue}'";
                    break;
            }
        }
        
        // Se não houver filtros, retornar SQL original
        if (empty($whereClauses)) {
            error_log("🔍 Nenhum filtro aplicado - SQL original");
            return $sql;
        }
        
        // Adicionar AND com filtros
        $whereClause = implode(' AND ', $whereClauses);
        
        // Adicionar filtros ANTES do ORDER BY (para preservar WHERE original)
        if (preg_match('/\bORDER\s+BY\b/i', $sql)) {
            // Tem ORDER BY - adicionar AND antes dele
            $sql = preg_replace('/\bORDER\s+BY\b/i', "AND {$whereClause} ORDER BY", $sql, 1);
            error_log("🔍 Filtros adicionados antes do ORDER BY");
        } else {
            // Não tem ORDER BY - adicionar no final
            $sql .= " AND {$whereClause}";
            error_log("🔍 Filtros adicionados no final da query");
        }
        
        error_log("🔍 Filtros aplicados: " . $whereClause);
        
        return $sql;
    }
    
    /**
     * Aplicar filtros substituindo variáveis (método antigo - mantido para compatibilidade)
     */
    private function applyFilters(string $sql, array $filters, array $filtersConfig): string
    {
        // Substituir variáveis de filtro configuradas
        foreach ($filtersConfig as $filter) {
            $field = $filter['field'] ?? '';
            $variable = $filter['variable'] ?? '';
            $type = $filter['type'] ?? 'text';
            
            if (!$variable || !isset($filters[$field])) {
                continue;
            }
            
            $value = $filters[$field];
            
            // Se vazio, remover o filtro
            if (empty($value)) {
                $sql = str_replace($variable, '', $sql);
                continue;
            }
            
            // Aplicar filtro conforme tipo
            switch ($type) {
                case 'year':
                case 'number':
                    $sql = str_replace($variable, $value, $sql);
                    break;
                    
                case 'month':
                    if (is_numeric($value)) {
                        $sql = str_replace($variable, "AND MONTH(T0.\"DocDate\") = {$value}", $sql);
                    } else {
                        $sql = str_replace($variable, '', $sql);
                    }
                    break;
                    
                case 'text':
                default:
                    $escapedValue = addslashes($value);
                    $sql = str_replace($variable, "AND {$field} = '{$escapedValue}'", $sql);
                    break;
            }
        }
        
        return $sql;
    }
    
    /**
     * Calcular medidas usando fórmulas (estilo DAX)
     */
    private function calculateMeasures(array $data, array $measuresConfig): array
    {
        $measures = [];
        
        foreach ($measuresConfig as $measure) {
            $name = $measure['name'] ?? '';
            $formula = $measure['formula'] ?? '';
            
            if (empty($name) || empty($formula)) {
                continue;
            }
            
            try {
                $value = $this->evaluateFormula($formula, $data);
                $measures[$name] = $value;
                
                error_log("✅ Medida calculada: {$name} = {$value}");
            } catch (\Exception $e) {
                error_log("❌ Erro ao calcular medida {$name}: " . $e->getMessage());
                $measures[$name] = 0;
            }
        }
        
        return $measures;
    }
    
    /**
     * Avaliar fórmula (estilo DAX/Power BI)
     * Suporta: CALCULATE, SUM com filtros, referências a tabelas, medidas calculadas
     */
    private function evaluateFormula(string $formula, array $data): float
    {
        // Normalizar espaços e quebras de linha
        $formula = preg_replace('/\s+/', ' ', trim($formula));
        $formula = str_replace(["\n", "\r", "\t"], ' ', $formula);
        
        // Processar CALCULATE recursivamente (mais interno primeiro)
        while (preg_match('/CALCULATE\s*\(/i', $formula)) {
            $formula = $this->processCalculateRecursive($formula, $data);
        }
        
        // Avaliar expressão final
        return $this->evaluateExpression($formula, $data);
    }
    
    /**
     * Processar CALCULATE recursivamente, resolvendo do mais interno para o mais externo
     */
    private function processCalculateRecursive(string $formula, array $data): string
    {
        // Encontrar o CALCULATE mais interno (sem outro CALCULATE dentro)
        $pattern = '/CALCULATE\s*\(\s*([^()]*?)(?:\s*;\s*([^)]+))?\s*\)/i';
        
        // Se não encontrar mais CALCULATE simples, procurar por CALCULATE com parênteses aninhados
        if (!preg_match($pattern, $formula)) {
            // Procurar CALCULATE com conteúdo que pode ter parênteses balanceados
            $pos = 0;
            while (($pos = stripos($formula, 'CALCULATE', $pos)) !== false) {
                $startPos = $pos;
                $openPos = $pos + 8; // Posição após "CALCULATE"
                
                // Pular espaços
                while (isset($formula[$openPos]) && $formula[$openPos] === ' ') {
                    $openPos++;
                }
                
                if (!isset($formula[$openPos]) || $formula[$openPos] !== '(') {
                    $pos++;
                    continue;
                }
                
                // Encontrar o parêntese de fechamento correspondente
                $openCount = 1;
                $currentPos = $openPos + 1;
                $expressionStart = $currentPos;
                $expressionEnd = null;
                $filtersStart = null;
                $filtersEnd = null;
                
                while ($openCount > 0 && isset($formula[$currentPos])) {
                    if ($formula[$currentPos] === '(') {
                        $openCount++;
                    } elseif ($formula[$currentPos] === ')') {
                        $openCount--;
                        if ($openCount === 0) {
                            $expressionEnd = $currentPos;
                            break;
                        }
                    } elseif ($formula[$currentPos] === ';' && $openCount === 1) {
                        // Separador entre expressão e filtros
                        $expressionEnd = $currentPos - 1;
                        $filtersStart = $currentPos + 1;
                    }
                    $currentPos++;
                }
                
                if ($expressionEnd === null) {
                    $pos++;
                    continue;
                }
                
                // Extrair expressão e filtros
                $expression = trim(substr($formula, $expressionStart, $expressionEnd - $expressionStart));
                $filters = '';
                
                if ($filtersStart !== null) {
                    $filtersEnd = $currentPos - 1;
                    $filters = trim(substr($formula, $filtersStart, $filtersEnd - $filtersStart));
                }
                
                // Verificar se a expressão contém outro CALCULATE (se sim, pular este)
                if (stripos($expression, 'CALCULATE') !== false) {
                    $pos++;
                    continue;
                }
                
                // Processar este CALCULATE
                $filteredData = $data;
                if (!empty($filters)) {
                    $filteredData = $this->applyFormulaFilters($filteredData, $filters);
                }
                
                $value = $this->evaluateExpression($expression, $filteredData);
                
                // Substituir CALCULATE(...) pelo valor
                $fullMatch = substr($formula, $startPos, $currentPos - $startPos + 1);
                $formula = str_replace($fullMatch, (string)$value, $formula);
                
                return $formula;
            }
            
            // Se não encontrou nenhum CALCULATE processável, retornar
            return $formula;
        }
        
        // Processar CALCULATE simples (sem aninhamento)
        preg_match($pattern, $formula, $matches);
        $expression = trim($matches[1]);
        $filters = isset($matches[2]) ? trim($matches[2]) : '';
        
        // Aplicar filtros aos dados se houver
        $filteredData = $data;
        if (!empty($filters)) {
            $filteredData = $this->applyFormulaFilters($filteredData, $filters);
        }
        
        // Avaliar expressão com dados filtrados
        $value = $this->evaluateExpression($expression, $filteredData);
        
        // Substituir CALCULATE(...) pelo valor calculado
        $formula = str_replace($matches[0], (string)$value, $formula);
        
        return $formula;
    }
    
    /**
     * Aplicar filtros aos dados (formato: TABELA[CAMPO]="VALOR" ou TABELA[CAMPO]="VALOR";TABELA[CAMPO2]="VALOR2")
     */
    private function applyFormulaFilters(array $data, string $filters): array
    {
        if (empty($data)) {
            return $data;
        }
        
        $filtered = $data;
        
        // Dividir múltiplos filtros (separados por ;)
        $filterParts = array_map('trim', explode(';', $filters));
        
        foreach ($filterParts as $filterPart) {
            if (empty($filterPart)) continue;
            
            // Padrão: TABELA[CAMPO]="VALOR" ou TABELA[CAMPO]='VALOR'
            if (preg_match('/(?:\[([^\]]+)\]|([A-Za-z_][A-Za-z0-9_]*)\[([^\]]+)\])\s*=\s*["\']([^"\']+)["\']/i', $filterPart, $matches)) {
                // Formato: [CAMPO]="VALOR"
                if (isset($matches[1]) && !empty($matches[1])) {
                    $field = $matches[1];
                    $value = $matches[4];
                }
                // Formato: TABELA[CAMPO]="VALOR"
                elseif (isset($matches[2]) && isset($matches[3])) {
                    $field = $matches[3]; // Ignorar nome da tabela, usar apenas o campo
                    $value = $matches[4];
                } else {
                    continue;
                }
                
                // Filtrar dados
                $filtered = array_filter($filtered, function($row) use ($field, $value) {
                    $rowValue = $row[$field] ?? '';
                    // Comparação flexível (string ou numérico)
                    return (string)$rowValue === (string)$value;
                });
            }
            // Padrão numérico: TABELA[CAMPO]=123
            elseif (preg_match('/(?:\[([^\]]+)\]|([A-Za-z_][A-Za-z0-9_]*)\[([^\]]+)\])\s*=\s*([0-9.]+)/i', $filterPart, $matches)) {
                if (isset($matches[1]) && !empty($matches[1])) {
                    $field = $matches[1];
                    $value = (float)$matches[4];
                } elseif (isset($matches[2]) && isset($matches[3])) {
                    $field = $matches[3];
                    $value = (float)$matches[4];
                } else {
                    continue;
                }
                
                $filtered = array_filter($filtered, function($row) use ($field, $value) {
                    $rowValue = (float)($row[$field] ?? 0);
                    return abs($rowValue - $value) < 0.0001; // Comparação numérica
                });
            }
        }
        
        return array_values($filtered); // Reindexar
    }
    
    /**
     * Avaliar expressão (sem CALCULATE) - processa SUM, AVG, COUNT, etc.
     */
    private function evaluateExpression(string $expression, array $data): float
    {
        // Processar SUM com filtros: SUM(TABELA[CAMPO];TABELA[CAMPO]="VALOR")
        while (preg_match('/SUM\s*\(\s*([^;]+)(?:\s*;\s*([^)]+))?\s*\)/i', $expression, $matches)) {
            $fieldExpr = trim($matches[1]);
            $filters = isset($matches[2]) ? trim($matches[2]) : '';
            
            // Extrair nome do campo
            $field = $this->extractFieldFromExpression($fieldExpr);
            
            if (!$field) {
                throw new \Exception("Campo inválido em SUM: {$fieldExpr}");
            }
            
            // Aplicar filtros se houver
            $filteredData = $data;
            if (!empty($filters)) {
                $filteredData = $this->applyFormulaFilters($filteredData, $filters);
            }
            
            $value = array_sum(array_column($filteredData, $field));
            $expression = str_replace($matches[0], (string)$value, $expression);
        }
        
        // Processar AVG
        $expression = preg_replace_callback('/AVG\s*\(\s*\[([^\]]+)\]\s*\)/i', function($matches) use ($data) {
            $field = $matches[1];
            $values = array_column($data, $field);
            return count($values) > 0 ? array_sum($values) / count($values) : 0;
        }, $expression);
        
        // Processar COUNT
        $expression = preg_replace_callback('/COUNT\s*\(\s*\[([^\]]+)\]\s*\)/i', function($matches) use ($data) {
            return count($data);
        }, $expression);
        
        // Processar COUNT_DISTINCT
        $expression = preg_replace_callback('/COUNT_DISTINCT\s*\(\s*\[([^\]]+)\]\s*\)/i', function($matches) use ($data) {
            $field = $matches[1];
            return count(array_unique(array_column($data, $field)));
        }, $expression);
        
        // Processar MIN
        $expression = preg_replace_callback('/MIN\s*\(\s*\[([^\]]+)\]\s*\)/i', function($matches) use ($data) {
            $field = $matches[1];
            $values = array_column($data, $field);
            return !empty($values) ? min($values) : 0;
        }, $expression);
        
        // Processar MAX
        $expression = preg_replace_callback('/MAX\s*\(\s*\[([^\]]+)\]\s*\)/i', function($matches) use ($data) {
            $field = $matches[1];
            $values = array_column($data, $field);
            return !empty($values) ? max($values) : 0;
        }, $expression);
        
        // Substituir referências a tabelas: TABELA[CAMPO] -> CAMPO
        $expression = preg_replace_callback('/([A-Za-z_][A-Za-z0-9_]*)\[([^\]]+)\]/i', function($matches) use ($data) {
            $field = $matches[2]; // Ignorar nome da tabela
            return array_sum(array_column($data, $field));
        }, $expression);
        
        // Substituir referências diretas a campos: [CAMPO]
        $expression = preg_replace_callback('/\[([^\]]+)\]/', function($matches) use ($data) {
            $field = $matches[1];
            return array_sum(array_column($data, $field));
        }, $expression);
        
        // Normalizar decimais (vírgula -> ponto)
        $expression = str_replace(',', '.', $expression);
        
        // Validar que só tem números e operadores
        if (!preg_match('/^[\d\s+\-*\/().]+$/', $expression)) {
            throw new \Exception("Fórmula inválida após substituições: {$expression}");
        }
        
        // Avaliar expressão matemática
        $result = @eval("return {$expression};");
        
        if ($result === false) {
            throw new \Exception("Erro ao avaliar expressão: {$expression}");
        }
        
        return (float)$result;
    }
    
    /**
     * Extrair nome do campo de uma expressão (suporta [CAMPO] ou TABELA[CAMPO])
     */
    private function extractFieldFromExpression(string $expression): ?string
    {
        // Formato: [CAMPO]
        if (preg_match('/\[([^\]]+)\]/', $expression, $matches)) {
            return $matches[1];
        }
        
        // Formato: TABELA[CAMPO]
        if (preg_match('/([A-Za-z_][A-Za-z0-9_]*)\[([^\]]+)\]/i', $expression, $matches)) {
            return $matches[2];
        }
        
        // Formato: CAMPO (sem colchetes)
        $expression = trim($expression);
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $expression)) {
            return $expression;
        }
        
        return null;
    }
    
    private function calculateKPIs(array $data, array $kpisConfig, array $measures = []): array
    {
        $kpis = [];
        
        foreach ($kpisConfig as $kpi) {
            $field = $kpi['field'] ?? '';
            $aggregation = $kpi['aggregation'] ?? 'sum';
            $label = $kpi['label'] ?? $field;
            
            $value = 0;
            
            // Verificar se é uma medida calculada
            if (isset($measures[$field])) {
                $value = $measures[$field];
            } else {
                // Agregação normal de campo
                switch ($aggregation) {
                    case 'sum':
                        $value = array_sum(array_column($data, $field));
                        break;
                    case 'avg':
                        $values = array_column($data, $field);
                        $value = count($values) > 0 ? array_sum($values) / count($values) : 0;
                        break;
                    case 'count':
                        $value = count($data);
                        break;
                    case 'count_distinct':
                        $value = count(array_unique(array_column($data, $field)));
                        break;
                    case 'min':
                        $values = array_column($data, $field);
                        $value = !empty($values) ? min($values) : 0;
                        break;
                    case 'max':
                        $values = array_column($data, $field);
                        $value = !empty($values) ? max($values) : 0;
                        break;
                }
            }
            
            $kpis[$label] = [
                'value' => $value,
                'format' => $kpi['format'] ?? 'number',
                'icon' => $kpi['icon'] ?? 'fa-chart-line',
                'color' => $kpi['color'] ?? 'primary'
            ];
        }
        
        return $kpis;
    }
    
    private function aggregateForCharts(array $data, array $chartsConfig): array
    {
        $charts = [];
        
        foreach ($chartsConfig as $chartKey => $chart) {
            $groupBy = $chart['group_by'] ?? '';
            $valueField = $chart['value_field'] ?? '';
            $aggregation = $chart['aggregation'] ?? 'sum';
            
            if (!$groupBy || !$valueField) {
                continue;
            }
            
            $grouped = [];
            
            foreach ($data as $row) {
                $key = $row[$groupBy] ?? 'Sem Categoria';
                
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [];
                }
                
                $grouped[$key][] = (float)($row[$valueField] ?? 0);
            }
            
            // Agregar
            $result = [];
            foreach ($grouped as $key => $values) {
                switch ($aggregation) {
                    case 'sum':
                        $result[$key] = array_sum($values);
                        break;
                    case 'avg':
                        $result[$key] = count($values) > 0 ? array_sum($values) / count($values) : 0;
                        break;
                    case 'count':
                        $result[$key] = count($values);
                        break;
                }
            }
            
            $charts[$chartKey] = $result;
        }
        
        return $charts;
    }
}

