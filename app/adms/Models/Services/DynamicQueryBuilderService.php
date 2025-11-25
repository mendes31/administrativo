<?php

namespace App\adms\Models\Services;

use PDO;
use Exception;
use App\adms\Models\Services\ReportCacheService;
use App\adms\Models\Services\SapReportApiService;

class DynamicQueryBuilderService
{
    private ?DbConnection $dbConnection = null;
    private ?PDO $localConnection = null;
    private string $connectionType = 'local';
    private ?SapB1ServiceLayer $sapServiceLayer = null;
    private ?ReportCacheService $cacheService = null;
    private ?SapReportApiService $sapApiService = null;

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
        // SEMPRE usar API para SAP (não mais conexão direta ODBC)
        // Este método só é usado para conexões locais agora
        if ($this->connectionType === 'sap_api' || $this->connectionType === 'sap_api') {
            throw new Exception('Conexão SAP deve usar API, não conexão direta. Use executeSapApiQuery()');
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
        error_log("🔷 DynamicQueryBuilderService::executeReport - INÍCIO");
        error_log("🔷 query_mode: " . ($config['query_mode'] ?? 'não definido'));
        error_log("🔷 custom_sql presente: " . (!empty($config['custom_sql']) ? 'SIM' : 'NÃO'));
        
        $forceRefresh = (bool)($config['force_refresh'] ?? false);
        $cacheNamespace = $config['cache_namespace'] ?? null;

        // Verificar se é SQL personalizado
        if (!empty($config['custom_sql']) || ($config['query_mode'] ?? 'builder') === 'custom_sql') {
            error_log("🔷 Modo: SQL Personalizado - Chamando executeCustomSQL");
            $config['force_refresh'] = $forceRefresh;
            $config['cache_namespace'] = $cacheNamespace;
            return $this->executeCustomSQL($config);
        }
        
        $dataSource = $config['data_source'] ?? '';
        $isSapB1 = $this->isSapB1Table($dataSource);
        
        if ($isSapB1) {
            // SEMPRE usar API para SAP (não mais conexão direta ODBC)
            $this->setConnection('sap_api');
        } else {
            $this->setConnection('local');
        }
        
        $startTime = microtime(true);
        
        try {
            $sql = $this->buildQuery($config);
            
            // Paginação
            $page = (int)($config['page'] ?? 1);
            $perPage = (int)($config['per_page'] ?? 25);
            
            // Se for SAP, usar API (não mais ODBC HANA direto)
            if ($isSapB1) {
                error_log("🔷 Executando Builder via API SAP: $sql");
                
                $forceRefresh = (bool)($config['force_refresh'] ?? false);
                $cacheNamespace = $config['cache_namespace'] ?? null;
                
                return $this->executeSapApiQuery($sql, $forceRefresh, $cacheNamespace, $page, $perPage);
            }
            
            // Se for local, usar PDO com paginação
            $params = $this->extractParameters($config);
            
            // Contar total de registros
            $countSql = "SELECT COUNT(*) as total FROM ({$sql}) as count_query";
            $pdo = $this->getActiveConnection();
            $countStmt = $pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalRows = (int)$countStmt->fetchColumn();
            
            // Aplicar LIMIT/OFFSET
            $offset = max(0, ($page - 1) * $perPage);
            if (!preg_match('/\bLIMIT\s+\d+/i', $sql)) {
                $sql .= " LIMIT {$perPage} OFFSET {$offset}";
            }
            
            $stmt = $pdo->prepare($sql);
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
                'total_rows' => $totalRows,
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
        error_log("🔷 DynamicQueryBuilderService::executeCustomSQL - INÍCIO");
        
        $sql = trim($config['custom_sql'] ?? '');
        error_log("🔷 SQL recebido (raw): [" . substr($sql, 0, 200) . "]");
        error_log("🔷 SQL length: " . strlen($sql));
        
        if (empty($sql)) {
            error_log("❌ SQL vazio!");
            return ['success' => false, 'error' => 'SQL personalizado não fornecido'];
        }
        
        // Remover números ou caracteres inválidos no início (comum em editores como Monaco)
        $sql = preg_replace('/^[\d\s]+/i', '', $sql);
        $sql = trim($sql);
        error_log("🔷 SQL após limpeza: [" . substr($sql, 0, 200) . "]");
        
        // Validar que é apenas SELECT
        if (!preg_match('/^\s*SELECT\s+/i', $sql)) {
            error_log("❌ Validação falhou - não começa com SELECT");
            return ['success' => false, 'error' => 'Apenas queries SELECT são permitidas. SQL recebido: ' . substr($sql, 0, 50)];
        }
        
        // Detectar se é SAP B1 pela SQL
        $connectionType = $this->detectConnectionFromSQL($sql);
        error_log("🔷 detectConnectionFromSQL retornou: {$connectionType}");
        $this->setConnection($connectionType);
        error_log("🔷 Connection type após setConnection: {$this->connectionType}");
        
        $startTime = microtime(true);
        $forceRefresh = (bool)($config['force_refresh'] ?? false);
        $cacheNamespace = $config['cache_namespace'] ?? null;
        $page = (int)($config['page'] ?? 1);
        $perPage = (int)($config['per_page'] ?? 25);
        
        try {
            // SEMPRE usar API para SAP (não mais conexão direta ODBC)
            if ($connectionType === 'sap_api') {
                error_log("🔷 Connection type é sap_api - Chamando executeSapApiQuery");
                return $this->executeSapApiQuery($sql, $forceRefresh, $cacheNamespace, $page, $perPage);
            }
            
            // Se for local, usar PDO com paginação
            error_log("✅ Executando via PDO Local: $sql");
            
            // Contar total de registros (sem LIMIT)
            $countSql = "SELECT COUNT(*) as total FROM ({$sql}) as count_query";
            $pdo = $this->getActiveConnection();
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute();
            $totalRows = (int)$countStmt->fetchColumn();
            
            // Aplicar LIMIT/OFFSET
            $offset = max(0, ($page - 1) * $perPage);
            if (!preg_match('/\bLIMIT\s+\d+/i', $sql)) {
                $sql .= " LIMIT {$perPage} OFFSET {$offset}";
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $executionTime = microtime(true) - $startTime;
            
            return [
                'success' => true,
                'data' => $results,
                'rows_count' => count($results),
                'total_rows' => $totalRows,
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
     * SEMPRE usa API para SAP (não mais conexão direta ODBC)
     */
    private function detectConnectionFromSQL(string $sql): string
    {
        if (self::hasSapSignature($sql)) {
            error_log("🔍 Query SAP detectada → Usando API SAP (não mais ODBC direto)");
            return 'sap_api';
        }

        error_log("ℹ️ Nenhuma tabela SAP detectada → Usando conexão local");
        return 'local';
    }

    public static function hasSapSignature(string $sql): bool
    {
        $sapB1Tables = [
            'OCRD', 'OCRG', 'CRD1',
            'OINV', 'INV1', 'ORDR', 'RDR1', 'OQUT', 'QUT1', 'ORDN', 'RDN1', 'ORIN', 'RIN1', 'RIN3', 'RIN12',
            'OPCH', 'PCH1', 'OPOR', 'POR1', 'OPRQ', 'PRQ1', 'OPDN', 'PDN1',
            'OITM', 'OITB', 'OITW',
            'OSLP',
            'OUSG',
            'OBPL',
            'OJDT', 'JDT1', 'OACT',
            'OADM', 'ONNM'
        ];

        foreach ($sapB1Tables as $table) {
            if (stripos($sql, $table) !== false) {
                return true;
            }
        }

        return false;
    }

    private function executeSapApiQuery(string $sql, bool $forceRefresh, ?string $namespace, int $page = 1, int $perPage = 25): array
    {
        try {
            error_log("🔷 DynamicQueryBuilderService::executeSapApiQuery - INÍCIO");
            error_log("🔷 SQL recebido: " . substr($sql, 0, 200));
            
            $cacheKey = $this->buildCacheKey($sql, $namespace);
            $cacheService = $this->getCacheService();

            error_log("🔷 executeSapApiQuery - SQL: " . substr($sql, 0, 100));
            error_log("🔷 executeSapApiQuery - forceRefresh: " . ($forceRefresh ? 'true' : 'false'));
            error_log("🔷 executeSapApiQuery - cacheKey: " . $cacheKey);

            if (!$forceRefresh) {
                $cached = $cacheService->get($cacheKey);
                if ($cached) {
                    error_log("✅ Cache encontrado para: " . $cacheKey);
                    $payload = $cached['data'];
                    // Não retornar cache se for erro
                    if (isset($payload['success']) && $payload['success'] === false) {
                        error_log("⚠️ Cache contém erro, ignorando e fazendo nova consulta");
                        $cacheService->forget($cacheKey);
                    } else {
                        // Garantir que execution_time e rows_count existam mesmo vindo do cache
                        if (!isset($payload['execution_time'])) {
                            $payload['execution_time'] = 0.0;
                        }
                        if (!isset($payload['rows_count'])) {
                            $payload['rows_count'] = count($payload['data'] ?? []);
                        }
                        
                        // Aplicar paginação nos dados do cache
                        $allData = $payload['data'] ?? [];
                        $totalRows = count($allData);
                        if (!isset($payload['total_rows'])) {
                            $payload['total_rows'] = $totalRows;
                        }
                        $offset = max(0, ($page - 1) * $perPage);
                        $paginatedData = array_slice($allData, $offset, $perPage);
                        $payload['data'] = $paginatedData;
                        $payload['rows_count'] = count($paginatedData);
                        
                        $payload['cache'] = [
                            'from_cache' => true,
                            'stored_at' => $cached['stored_at'],
                            'cache_key' => $cacheKey
                        ];
                        return $payload;
                    }
                } else {
                    error_log("ℹ️ Nenhum cache encontrado");
                }
            } else {
                error_log("🔄 Forçando refresh, limpando cache");
                $cacheService->forget($cacheKey);
            }

            error_log("📡 Chamando API SAP...");
            try {
                $apiResult = $this->getSapApiService()->execute($sql);
                error_log("✅ API SAP retornou: " . (isset($apiResult['rows_count']) ? $apiResult['rows_count'] . ' registros' : 'erro'));
            } catch (\Exception $e) {
                error_log("❌ ERRO ao chamar API SAP: " . $e->getMessage());
                error_log("❌ Arquivo: " . $e->getFile() . ":" . $e->getLine());
                throw $e;
            }
            // Aplicar paginação nos dados retornados da API
            $allData = $this->convertEncodingToUtf8($apiResult['data']);
            $totalRows = count($allData);
            $offset = max(0, ($page - 1) * $perPage);
            $paginatedData = array_slice($allData, $offset, $perPage);
            
            $result = [
                'success' => true,
                'data' => $paginatedData,
                'rows_count' => count($paginatedData),
                'total_rows' => $totalRows,
                'execution_time' => (float)($apiResult['execution_time'] ?? 0.0),
                'connection_type' => 'sap_api',
                'sql' => $sql,
                'query_mode' => 'custom_sql'
            ];

            $cacheService->put($cacheKey, $result);
            $result['cache'] = [
                'from_cache' => false,
                'stored_at' => time(),
                'cache_key' => $cacheKey
            ];

            error_log("✅ executeSapApiQuery - SUCESSO - Retornando " . count($result['data'] ?? []) . " registros");
            return $result;
        } catch (Exception $e) {
            error_log("❌ ERRO em executeSapApiQuery: " . $e->getMessage());
            error_log("❌ Arquivo: " . $e->getFile() . ":" . $e->getLine());
            error_log("❌ Stack trace: " . substr($e->getTraceAsString(), 0, 500));
            
            // Limpar cache em caso de erro para evitar retornar erro em cache
            if (isset($cacheKey)) {
                $cacheService->forget($cacheKey);
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'connection_type' => 'sap_api',
                'sql' => $sql
            ];
        }
    }

    private function buildCacheKey(string $sql, ?string $namespace): string
    {
        $prefix = $namespace ? preg_replace('/[^a-z0-9_\-]/i', '_', $namespace) : 'generic';
        return $prefix . '_' . hash('sha256', $sql);
    }

    private function getCacheService(): ReportCacheService
    {
        if ($this->cacheService === null) {
            $this->cacheService = new ReportCacheService();
        }

        return $this->cacheService;
    }

    private function getSapApiService(): SapReportApiService
    {
        if ($this->sapApiService === null) {
            $this->sapApiService = new SapReportApiService();
        }

        return $this->sapApiService;
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
                // utf8_encode está deprecated, usar mb_convert_encoding como alternativa
                $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
            }

        return $value;
    }
}

