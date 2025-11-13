<?php

namespace App\adms\Models\Services;

use PDO;
use Exception;

class DynamicQueryBuilderService
{
    private ?DbConnection $dbConnection = null;
    private ?PDO $localConnection = null;
    private string $connectionType = 'local';
    private ?SapB1ServiceLayer $sapServiceLayer = null;

    public function setConnection(string $type = 'local'): void
    {
        $this->connectionType = $type;
    }
    
    /**
     * Obter conexão MySQL local (apenas quando necessário)
     */
    private function getLocalConnection(): PDO
    {
        if ($this->localConnection === null) {
            // Conectar direto no MySQL sem usar DbConnection
            $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            $this->localConnection = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], $options);
        }
        return $this->localConnection;
    }
    
    private function getActiveConnection(): PDO
    {
        if ($this->connectionType === 'sap_b1') {
            return SapB1HanaConnection::getInstance();
        }
        return $this->getLocalConnection();
    }
    
    /**
     * Obter instância da Service Layer do SAP
     */
    private function getSapServiceLayer(): SapB1ServiceLayer
    {
        if ($this->sapServiceLayer === null) {
            $this->sapServiceLayer = new SapB1ServiceLayer();
        }
        return $this->sapServiceLayer;
    }

    public function executeReport(array $config): array
    {
        // Verificar se é SQL personalizado
        if (!empty($config['custom_sql']) || ($config['query_mode'] ?? 'builder') === 'custom_sql') {
            return $this->executeCustomSQL($config);
        }
        
        $dataSource = $config['data_source'] ?? '';
        $isSapB1 = $this->isSapB1Table($dataSource);
        
        if ($isSapB1) {
            $this->setConnection('sap_b1');
        } else {
            $this->setConnection('local');
        }
        
        $startTime = microtime(true);
        
        try {
            $sql = $this->buildQuery($config);
            
            // Se for SAP B1, usar ODBC HANA também no modo Builder
            if ($isSapB1) {
                error_log("🔷 Executando Builder via ODBC HANA: $sql");
                
                $hanaConnection = SapB1HanaConnection::getInstance();
                $stmt = $hanaConnection->query($sql);
                $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $executionTime = microtime(true) - $startTime;
                
                // Converter encoding para UTF-8
                $results = $this->convertEncodingToUtf8($results);
                
                return [
                    'success' => true,
                    'data' => $results,
                    'rows_count' => count($results),
                    'execution_time' => round($executionTime, 4),
                    'connection_type' => 'sap_b1',
                    'sql' => $sql,
                    'query_mode' => 'builder'
                ];
            }
            
            // Se for local, usar PDO
            $params = $this->extractParameters($config);
            $stmt = $this->getActiveConnection()->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $executionTime = microtime(true) - $startTime;
            
            return [
                'success' => true,
                'data' => $results,
                'rows_count' => count($results),
                'execution_time' => round($executionTime, 4),
                'connection_type' => $this->connectionType,
                'sql' => $sql
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'connection_type' => $this->connectionType
            ];
        }
    }

    private function buildQuery(array $config): string
    {
        $dataSource = $this->sanitizeIdentifier($config['data_source']);
        $fields = $config['fields'] ?? [];
        $filters = $config['filters'] ?? [];
        $groupby = $config['groupby'] ?? [];
        $orderby = $config['orderby'] ?? [];
        
        $selectFields = $this->buildSelectFields($fields, $groupby);
        $sql = "SELECT {$selectFields} FROM {$dataSource}";
        
        $whereClause = $this->buildWhereClause($filters);
        if ($whereClause) $sql .= " WHERE {$whereClause}";
        
        if (!empty($groupby)) {
            $groupByFields = array_map(fn($field) => $this->sanitizeIdentifier($field), $groupby);
            $sql .= " GROUP BY " . implode(', ', $groupByFields);
        }
        
        if (!empty($orderby)) {
            $sql .= " ORDER BY " . $this->buildOrderByClause($orderby);
        }
        
        if (isset($config['limit'])) {
            $sql .= " LIMIT " . (int)$config['limit'];
        }
        
        return $sql;
    }

    /**
     * Executar SQL personalizado
     */
    private function executeCustomSQL(array $config): array
    {
        $sql = trim($config['custom_sql'] ?? '');
        
        if (empty($sql)) {
            return ['success' => false, 'error' => 'SQL personalizado não fornecido'];
        }
        
        // Validar que é apenas SELECT
        if (!preg_match('/^\s*SELECT\s+/i', $sql)) {
            return ['success' => false, 'error' => 'Apenas queries SELECT são permitidas'];
        }
        
        // Detectar se é SAP B1 pela SQL
        $connectionType = $this->detectConnectionFromSQL($sql);
        $this->setConnection($connectionType);
        
        $startTime = microtime(true);
        
        try {
            // Se for SAP B1, usar ODBC/HDBODBC (não Service Layer)
            if ($connectionType === 'sap_b1') {
                error_log("🔷 Executando via ODBC HANA: $sql");
                
                try {
                    $hanaConnection = SapB1HanaConnection::getInstance();
                    
                    // Verificar se a query já tem LIMIT
                    $hasLimit = preg_match('/\bLIMIT\s+\d+/i', $sql);
                    $disableAutoLimit = !empty($config['disable_auto_limit']);
                    $autoLimitApplied = false;
                    
                    // Se não tem LIMIT e não foi solicitado full refresh, aplicar automaticamente (máximo 1000 registros)
                    if (!$hasLimit && !$disableAutoLimit) {
                        $sql .= ' LIMIT 1000';
                        $autoLimitApplied = true;
                        error_log("⚠️ LIMIT automático aplicado: 1000 registros");
                    }
                    
                    $stmt = $hanaConnection->query($sql);
                    
                    // Buscar dados em chunks para economizar memória
                    $results = [];
                    $chunkSize = 500;
                    $totalFetched = 0;
                    $safetyLimit = $disableAutoLimit ? null : 5000;
                    
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $results[] = $this->normalizeRowEncoding($row);
                        $totalFetched++;
                        
                        // Limite de segurança: máximo 5000 registros em preview
                        if ($safetyLimit !== null && $totalFetched >= $safetyLimit) {
                            error_log("⚠️ Limite de segurança atingido: {$safetyLimit} registros");
                            break;
                        }
                    }
                    
                    $executionTime = microtime(true) - $startTime;
                    
                    // IMPORTANTE: Converter encoding dos dados do HANA para UTF-8
                    $results = $this->convertEncodingToUtf8($results);
                    
                    $rowCount = count($results);
                    $warning = null;
                    
                    if ($autoLimitApplied) {
                        $warning = "📊 LIMIT automático de 1000 registros aplicado. Para ver todos os dados, clique em 'Atualizar Dados'.";
                    } elseif ($safetyLimit !== null && $totalFetched >= $safetyLimit) {
                        $warning = "⚠️ Limite de segurança: mostrando apenas os primeiros {$safetyLimit} registros. Use filtros (datas, status) para reduzir o volume ou execute uma atualização completa.";
                    } elseif ($rowCount > 2000 && !$disableAutoLimit) {
                        $warning = "Query retornou {$rowCount} registros. Para melhor performance em relatórios, adicione filtros de data.";
                    }
                    
                    return [
                        'success' => true,
                        'data' => $results,
                        'rows_count' => $rowCount,
                        'execution_time' => round($executionTime, 4),
                        'connection_type' => 'sap_b1',
                        'sql' => $sql,
                        'query_mode' => 'custom_sql',
                        'warning' => $warning,
                        'auto_limit_applied' => $autoLimitApplied,
                        'safety_limit' => $safetyLimit,
                        'disable_auto_limit' => $disableAutoLimit
                    ];
                } catch (\PDOException $pdoEx) {
                    // Erro específico do SAP HANA via ODBC
                    $errorMsg = $pdoEx->getMessage();
                    $errorCode = $pdoEx->getCode();
                    
                    // Extrair mensagem mais limpa do HANA
                    if (preg_match('/SQLSTATE\[(\w+)\]: (.+)/', $errorMsg, $matches)) {
                        $errorMsg = $matches[2];
                    }
                    
                    error_log("❌ Erro SAP HANA: [{$errorCode}] {$errorMsg}");
                    
                    return [
                        'success' => false,
                        'error' => "Erro SAP B1 HANA: {$errorMsg}",
                        'error_code' => $errorCode,
                        'connection_type' => 'sap_b1',
                        'sql' => $sql
                    ];
                }
            }
            
            // Se for local, usar PDO
            error_log("✅ Executando via PDO Local: $sql");
            
            $stmt = $this->getActiveConnection()->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $executionTime = microtime(true) - $startTime;
            
            return [
                'success' => true,
                'data' => $results,
                'rows_count' => count($results),
                'execution_time' => round($executionTime, 4),
                'connection_type' => 'local',
                'sql' => $sql,
                'query_mode' => 'custom_sql',
                'disable_auto_limit' => !empty($config['disable_auto_limit'])
            ];
        } catch (\PDOException $e) {
            error_log("❌ Erro PDO: " . $e->getMessage());
            return [
                'success' => false,
                'error' => "Erro de banco de dados: " . $e->getMessage(),
                'connection_type' => $this->connectionType,
                'sql' => $sql,
                'error_code' => $e->getCode()
            ];
        } catch (\Exception $e) {
            error_log("❌ Erro geral: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'connection_type' => $this->connectionType,
                'sql' => $sql
            ];
        }
    }
    
    /**
     * Detectar conexão a partir do SQL
     */
    private function detectConnectionFromSQL(string $sql): string
    {
        // Tabelas típicas do SAP B1 (prefixos O, I, @ e outras conhecidas)
        $sapB1Tables = [
            // Parceiros de Negócio
            'OCRD', 'OCRG', 'CRD1',
            // Vendas
            'OINV', 'INV1', 'ORDR', 'RDR1', 'OQUT', 'QUT1', 'ORDN', 'RDN1', 'ORIN', 'RIN1', 'RIN3', 'RIN12',
            // Compras
            'OPCH', 'PCH1', 'OPOR', 'POR1', 'OPRQ', 'PRQ1', 'OPDN', 'PDN1',
            // Itens
            'OITM', 'OITB', 'OITW',
            // Vendedores
            'OSLP',
            // Utilização
            'OUSG',
            // Filiais
            'OBPL',
            // Financeiro
            'OJDT', 'JDT1', 'OACT',
            // Outros
            'OADM', 'ONNM'
        ];
        
        foreach ($sapB1Tables as $table) {
            if (stripos($sql, $table) !== false) {
                error_log("🔍 Tabela SAP detectada: {$table} → Usando conexão sap_b1");
                return 'sap_b1';
            }
        }
        
        error_log("ℹ️ Nenhuma tabela SAP detectada → Usando conexão local");
        return 'local';
    }

    private function buildSelectFields(array $fields, array $groupby): string
    {
        // Se não há campos, retornar * (todos os campos)
        if (empty($fields)) return '*';
        
        $selectParts = [];
        foreach ($fields as $field) {
            if (is_string($field)) {
                $selectParts[] = $this->sanitizeIdentifier($field);
            } elseif (is_array($field)) {
                $fieldName = $field['field'] ?? $field['name'] ?? '';
                $aggregate = $field['aggregate'] ?? null;
                $alias = $field['alias'] ?? null;
                
                $fieldExpr = $aggregate 
                    ? $this->buildAggregateFunction($aggregate, $fieldName)
                    : $this->sanitizeIdentifier($fieldName);
                
                if ($alias) $fieldExpr .= ' AS ' . $this->sanitizeIdentifier($alias);
                $selectParts[] = $fieldExpr;
            }
        }
        return implode(', ', $selectParts);
    }

    private function buildAggregateFunction(string $aggregate, string $field): string
    {
        $allowedAggregates = ['COUNT', 'SUM', 'AVG', 'MIN', 'MAX'];
        $aggregate = strtoupper($aggregate);
        
        if (!in_array($aggregate, $allowedAggregates)) {
            throw new Exception("Função de agregação inválida: {$aggregate}");
        }
        
        return ($aggregate === 'COUNT' && $field === '*') 
            ? 'COUNT(*)' 
            : "{$aggregate}(" . $this->sanitizeIdentifier($field) . ")";
    }

    private function buildWhereClause(array $filters): string
    {
        if (empty($filters)) return '';
        
        $conditions = [];
        foreach ($filters as $filter) {
            $field = $this->sanitizeIdentifier($filter['field']);
            $operator = $this->sanitizeOperator($filter['operator'] ?? '=');
            
            if (in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
                $conditions[] = "{$field} {$operator}";
            } elseif (in_array($operator, ['IN', 'NOT IN'])) {
                $placeholders = implode(', ', array_fill(0, count($filter['value']), '?'));
                $conditions[] = "{$field} {$operator} ({$placeholders})";
            } elseif ($operator === 'BETWEEN') {
                $conditions[] = "{$field} BETWEEN ? AND ?";
            } else {
                $conditions[] = "{$field} {$operator} ?";
            }
        }
        return implode(' AND ', $conditions);
    }

    private function buildOrderByClause(array $orderby): string
    {
        $orderParts = [];
        foreach ($orderby as $order) {
            $field = $this->sanitizeIdentifier($order['field'] ?? $order);
            $direction = strtoupper($order['direction'] ?? 'ASC');
            if (!in_array($direction, ['ASC', 'DESC'])) $direction = 'ASC';
            $orderParts[] = "{$field} {$direction}";
        }
        return implode(', ', $orderParts);
    }

    private function extractParameters(array $config): array
    {
        $params = [];
        $filters = $config['filters'] ?? [];
        
        foreach ($filters as $filter) {
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? null;
            
            if (in_array($operator, ['IS NULL', 'IS NOT NULL'])) continue;
            
            if (in_array($operator, ['IN', 'NOT IN'])) {
                foreach ($value as $v) $params[] = $v;
            } elseif ($operator === 'BETWEEN') {
                $params[] = $value[0];
                $params[] = $value[1];
            } else {
                $params[] = (in_array($operator, ['LIKE', 'NOT LIKE'])) ? "%{$value}%" : $value;
            }
        }
        return $params;
    }

    private function sanitizeIdentifier(string $identifier): string
    {
        $identifier = preg_replace('/[^a-zA-Z0-9_.]/', '', $identifier);
        if (strpos($identifier, '.') !== false) {
            $parts = explode('.', $identifier);
            return implode('.', array_map(fn($p) => "`{$p}`", $parts));
        }
        return "`{$identifier}`";
    }

    private function sanitizeOperator(string $operator): string
    {
        $allowedOperators = ['=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 
                            'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL', 'BETWEEN'];
        $operator = strtoupper(trim($operator));
        return in_array($operator, $allowedOperators) ? $operator : '=';
    }

    private function isSapB1Table(string $tableName): bool
    {
        return in_array(substr($tableName, 0, 1), ['O', 'I', '@']) || 
               strpos($tableName, 'SAP') !== false;
    }
    
    /**
     * Converter encoding dos dados do HANA para UTF-8
     * HANA retorna em ISO-8859-1 ou Windows-1252
     */
    private function convertEncodingToUtf8(array $data): array
    {
        foreach ($data as $index => $row) {
            if (is_array($row)) {
                $data[$index] = $this->normalizeRowEncoding($row);
            } elseif (is_string($row)) {
                $data[$index] = $this->normalizeValueEncoding($row);
            }
        }
        return $data;
    }

    private function normalizeRowEncoding(array $row): array
    {
        foreach ($row as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_string($value)) {
                $row[$column] = $this->normalizeValueEncoding($value);
            }
        }

        return $row;
    }

    private function normalizeValueEncoding(string $value): string
    {
        $cleaned = @preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        if ($cleaned !== null) {
            $value = $cleaned;
        }

        $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $value = mb_convert_encoding($value, 'UTF-8', $encoding);
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = utf8_encode($value);
        }

        return $value;
    }
}

