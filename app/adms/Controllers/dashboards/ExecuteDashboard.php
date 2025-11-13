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
            $fullRefresh = isset($_POST['full_refresh']) && $_POST['full_refresh'] === '1';

            if ($fullRefresh) {
                ini_set('memory_limit', '1024M');
            }
            
            $normalizedFilters = $this->normalizeFilters($filters);
            
            $repo = new DashboardsRepository();
            
            // Verificar acesso
            if (!$repo->canAccess($dashboardId, $userId)) {
                throw new \Exception('Sem permissão para acessar este dashboard');
            }
            
            $dashboard = $repo->getById($dashboardId);
            
            if (!$dashboard) {
                throw new \Exception('Dashboard não encontrado');
            }
            
            if (!$fullRefresh) {
                $cached = $this->loadCache($dashboardId, $normalizedFilters);
                if ($cached !== null) {
                    $cachedResult = $cached['result'];
                    $cachedResult['from_cache'] = true;
                    $cachedResult['cache_timestamp'] = $cached['generated_at'] ?? null;
                    echo json_encode($cachedResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    exit;
                }
            }
            
            $sqlParts = $this->buildSqlWithRelationships($dashboard['reports'] ?? [], $dashboard['relationships'] ?? []);
            $filterClauses = $this->buildFilterClauses(
                $filters,
                $dashboard['filters_config'] ?? [],
                $sqlParts['alias_map'],
                $sqlParts['primary_report_id']
            );
            $sql = $this->assembleSql($sqlParts, $filterClauses);

            $queryBuilder = new DynamicQueryBuilderService();
            $result = $queryBuilder->executeReport([
                'custom_sql' => $sql,
                'query_mode' => 'custom_sql',
                'disable_auto_limit' => $fullRefresh
            ]);
            
            if ($result['success']) {
                $result['relationships_applied'] = $sqlParts['applied_relationships'];
                if (!empty($sqlParts['pending_relationships'])) {
                    $result['relationships_pending'] = $sqlParts['pending_relationships'];
                }
                $result['filters_applied'] = $filterClauses;
                
                // Calcular medidas antes dos KPIs
                $measures = $dashboard['measures_config'] ?? [];
                if (!empty($measures)) {
                    $result['measures'] = $this->calculateMeasures($result['data'], $measures);
                }
                
                // Calcular KPIs baseados na configuração (pode usar medidas)
                $result['kpis'] = $this->calculateKPIs($result['data'], $dashboard['kpis_config'], $result['measures'] ?? []);
                
                // Agregar dados para gráficos
                $result['chart_data'] = $this->aggregateForCharts($result['data'], $dashboard['charts_config']);
                
                $result['from_cache'] = false;
                $result['cache_timestamp'] = date('c');
                $result['cache_source'] = $fullRefresh ? 'full-refresh' : (($result['auto_limit_applied'] ?? false) ? 'preview-limit' : 'query');
                $this->storeCache($dashboardId, $normalizedFilters, $result);
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
    
    private function buildSqlWithRelationships(array $reports, array $relationships): array
    {
        if (empty($reports)) {
            throw new \InvalidArgumentException('Nenhum relatório vinculado ao dashboard.');
        }

        $reportsById = [];
        $aliases = [];
        $primaryId = null;

        foreach ($reports as $report) {
            $reportId = (int)($report['report_id'] ?? $report['id'] ?? 0);
            if ($reportId === 0) {
                continue;
            }

            $reportsById[$reportId] = $report;
            $aliases[$reportId] = 'r' . $reportId;

            if (($report['is_primary'] ?? false) && $primaryId === null) {
                $primaryId = $reportId;
            }
        }

        if ($primaryId === null) {
            $primaryId = (int)array_key_first($reportsById);
        }

        if ($primaryId === 0 || !isset($reportsById[$primaryId])) {
            throw new \InvalidArgumentException('Relatório principal não identificado.');
        }

        $primarySql = $reportsById[$primaryId]['custom_sql'] ?? '';
        if (trim($primarySql) === '') {
            throw new \InvalidArgumentException('Relatório principal sem SQL configurado.');
        }

        $selectParts = [$aliases[$primaryId] . '.*'];
        $fromClause = sprintf('FROM (%s) AS %s', $this->wrapSubquery($primarySql), $aliases[$primaryId]);
        $joins = [];
        $whereConditions = [];
        $joined = [$primaryId => true];
        $processedRelationships = [];
        $pending = [];

        foreach ($relationships as $relationship) {
            if (isset($relationship['active']) && !$relationship['active']) {
                continue;
            }

            $primaryRelId = isset($relationship['primary_report_id']) ? (int)$relationship['primary_report_id'] : null;
            $foreignRelId = isset($relationship['foreign_report_id']) ? (int)$relationship['foreign_report_id'] : null;

            if (!$primaryRelId || !$foreignRelId) {
                continue;
            }

            if (!isset($reportsById[$primaryRelId], $reportsById[$foreignRelId])) {
                continue;
            }

            $pending[] = $relationship;
        }

        $maxIterations = max(count($pending), 1) * 4;
        $iteration = 0;

        while (!empty($pending) && $iteration < $maxIterations) {
            $progress = false;

            foreach ($pending as $index => $relationship) {
                $primaryRelId = (int)$relationship['primary_report_id'];
                $foreignRelId = (int)$relationship['foreign_report_id'];

                $primaryJoined = isset($joined[$primaryRelId]);
                $foreignJoined = isset($joined[$foreignRelId]);

                if (!$primaryJoined && !$foreignJoined) {
                    continue;
                }

                $baseId = $primaryJoined ? $primaryRelId : $foreignRelId;
                $joinId = $baseId === $primaryRelId ? $foreignRelId : $primaryRelId;

                $baseField = $baseId === $primaryRelId ? ($relationship['primary_field'] ?? '') : ($relationship['foreign_field'] ?? '');
                $joinField = $baseId === $primaryRelId ? ($relationship['foreign_field'] ?? '') : ($relationship['primary_field'] ?? '');

                if ($baseField === '' || $joinField === '') {
                    unset($pending[$index]);
                    continue;
                }

                $joinType = strtoupper($relationship['join_type'] ?? 'INNER');
                if (!in_array($joinType, ['INNER', 'LEFT'], true)) {
                    $joinType = 'INNER';
                }

                $baseAlias = $aliases[$baseId];
                $joinAlias = $aliases[$joinId];

                if (!isset($joined[$joinId])) {
                    $joinSql = $reportsById[$joinId]['custom_sql'] ?? '';
                    if (trim($joinSql) === '') {
                        unset($pending[$index]);
                        continue;
                    }

                    $joins[] = sprintf(
                        '%s JOIN (%s) AS %s ON %s = %s',
                        $joinType,
                        $this->wrapSubquery($joinSql),
                        $joinAlias,
                        $baseAlias . '.' . $this->quoteIdentifier($baseField),
                        $joinAlias . '.' . $this->quoteIdentifier($joinField)
                    );

                    $selectParts[] = $joinAlias . '.*';
                    $joined[$joinId] = true;
                } else {
                    $whereConditions[] = sprintf(
                        '%s.%s = %s.%s',
                        $aliases[$primaryRelId],
                        $this->quoteIdentifier($relationship['primary_field']),
                        $aliases[$foreignRelId],
                        $this->quoteIdentifier($relationship['foreign_field'])
                    );
                }

                $processedRelationships[] = $relationship;
                unset($pending[$index]);
                $progress = true;
            }

            if (!$progress) {
                break;
            }

            $iteration++;
        }

        return [
            'select' => $selectParts,
            'from' => $fromClause,
            'joins' => array_values($joins),
            'where' => $whereConditions,
            'alias_map' => $aliases,
            'primary_report_id' => $primaryId,
            'applied_relationships' => $processedRelationships,
            'pending_relationships' => array_values($pending)
        ];
    }

    private function buildFilterClauses(array $filters, array $filtersConfig, array $aliasMap, int $primaryReportId): array
    {
        $clauses = [];

        foreach ($filtersConfig as $filter) {
            $fieldKey = $filter['field'] ?? '';
            if ($fieldKey === '') {
                continue;
            }

            if (!array_key_exists($fieldKey, $filters)) {
                continue;
            }

            $value = $filters[$fieldKey];
            if ($value === '' || $value === null || (is_array($value) && empty(array_filter($value, fn($v) => $v !== '' && $v !== null)))) {
                continue;
            }

            $sourceReportId = isset($filter['source_report_id']) ? (int)$filter['source_report_id'] : null;
            $alias = $aliasMap[$sourceReportId] ?? $aliasMap[$primaryReportId] ?? null;

            if (!$alias) {
                continue;
            }

            $fieldExpression = $filter['source_field'] ?? $fieldKey;
            $identifier = $alias . '.' . $this->quoteIdentifier($fieldExpression);
            $type = $filter['type'] ?? 'text';
            $label = $filter['label'] ?? '';

            $isYearFilter = $type === 'year' || stripos($label, 'ano') !== false || stripos($label, 'year') !== false;
            $isMonthFilter = $type === 'month' || stripos($label, 'mês') !== false || stripos($label, 'mes') !== false;

            if ($isYearFilter) {
                $clauses[] = sprintf('YEAR(%s) = %d', $identifier, (int)$value);
                continue;
            }

            if ($isMonthFilter) {
                $clauses[] = sprintf('MONTH(%s) = %d', $identifier, (int)$value);
                continue;
            }

            if (is_array($value)) {
                $sanitized = array_values(array_filter($value, fn($v) => $v !== '' && $v !== null));
                if (empty($sanitized)) {
                    continue;
                }

                $escaped = array_map(fn($v) => "'" . str_replace("'", "''", (string)$v) . "'", $sanitized);
                $clauses[] = sprintf('%s IN (%s)', $identifier, implode(', ', $escaped));
                continue;
            }

            switch ($type) {
                case 'number':
                    if (!is_numeric($value)) {
                        continue 2;
                    }
                    $clauses[] = sprintf('%s = %d', $identifier, (int)$value);
                    break;

                case 'text':
                default:
                    $escapedValue = str_replace("'", "''", (string)$value);
                    $clauses[] = sprintf("%s = '%s'", $identifier, $escapedValue);
                    break;
            }
        }

        return $clauses;
    }

    private function assembleSql(array $parts, array $whereClauses): string
    {
        $selectClause = 'SELECT ' . implode(",\n       ", $parts['select']);
        $sql = $selectClause . "\n" . $parts['from'];

        if (!empty($parts['joins'])) {
            $sql .= "\n" . implode("\n", $parts['joins']);
        }

        $allConditions = array_merge($parts['where'], $whereClauses);
        if (!empty($allConditions)) {
            $sql .= "\nWHERE " . implode("\n  AND ", $allConditions);
        }

        return $sql;
    }

    private function wrapSubquery(string $sql): string
    {
        return rtrim(trim($sql), ';');
    }

    private function quoteIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return '""';
        }

        if (str_contains($identifier, '.')) {
            $parts = array_map('trim', explode('.', $identifier));
            $parts = array_map(function ($part) {
                $part = trim($part, '"');
                return '"' . $part . '"';
            }, $parts);
            return implode('.', $parts);
        }

        return '"' . trim($identifier, '"') . '"';
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

    private function normalizeFilters(array $filters): array
    {
        if (empty($filters)) {
            return [];
        }
        foreach ($filters as $key => $value) {
            if (is_string($value)) {
                $filters[$key] = trim($value);
            }
        }
        ksort($filters);
        return $filters;
    }

    private function getCacheDirectory(): string
    {
        $dir = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'dashboards';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private function getCacheFilePath(int $dashboardId, array $filters): string
    {
        $key = empty($filters) ? 'base' : md5(json_encode($filters));
        return $this->getCacheDirectory() . DIRECTORY_SEPARATOR . "dashboard_{$dashboardId}_{$key}.json";
    }

    private function loadCache(int $dashboardId, array $filters): ?array
    {
        $path = $this->getCacheFilePath($dashboardId, $filters);
        if (!is_file($path)) {
            return null;
        }
        $content = @file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $currentLimit = ini_get('memory_limit');
        if ($currentLimit !== false) {
            $bytes = $this->convertToBytes($currentLimit);
            if ($bytes > 0 && $bytes < 1024 * 1024 * 1024) { // menor que 1GB
                ini_set('memory_limit', '1024M');
            }
        } else {
            ini_set('memory_limit', '1024M');
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('⚠️ Falha ao decodificar cache do dashboard: ' . json_last_error_msg());
            return null;
        }

        if (!is_array($data) || empty($data['result'])) {
            return null;
        }
        return $data;
    }

    private function convertToBytes(string $limit): int
    {
        $unit = strtolower($limit[strlen($limit) - 1]);
        $value = (int)$limit;

        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    private function storeCache(int $dashboardId, array $filters, array $result): void
    {
        if (empty($result['success'])) {
            return;
        }

        $path = $this->getCacheFilePath($dashboardId, $filters);
        $resultForCache = $result;
        unset($resultForCache['from_cache'], $resultForCache['cache_timestamp']);

        $data = [
            'generated_at' => date('c'),
            'filters' => $filters,
            'result' => $resultForCache
        ];

        try {
            file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        } catch (\Throwable $e) {
            error_log('⚠️ Falha ao gravar cache do dashboard: ' . $e->getMessage());
        }
    }
}

