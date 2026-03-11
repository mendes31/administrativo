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
                $incremental = (bool)($config['incremental'] ?? false);
                $cacheNamespace = $config['cache_namespace'] ?? null;
                
                return $this->executeSapApiQuery($sql, $forceRefresh, $cacheNamespace, $page, $perPage, $incremental);
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
        $incremental = (bool)($config['incremental'] ?? false);
        $cacheNamespace = $config['cache_namespace'] ?? null;
        $page = (int)($config['page'] ?? 1);
        $perPage = (int)($config['per_page'] ?? 25);
        
        try {
            // SEMPRE usar API para SAP (não mais conexão direta ODBC)
            if ($connectionType === 'sap_api') {
                error_log("🔷 Connection type é sap_api - Chamando executeSapApiQuery (incremental: " . ($incremental ? 'true' : 'false') . ")");
                return $this->executeSapApiQuery($sql, $forceRefresh, $cacheNamespace, $page, $perPage, $incremental);
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

    private function executeSapApiQuery(string $sql, bool $forceRefresh, ?string $namespace, int $page = 1, int $perPage = 25, bool $incremental = false): array
    {
        try {
            error_log("🔷 DynamicQueryBuilderService::executeSapApiQuery - INÍCIO");
            error_log("🔷 SQL recebido: " . substr($sql, 0, 200));
            
            $cacheKey = $this->buildCacheKey($sql, $namespace);
            $cacheService = $this->getCacheService();

            error_log("🔷 executeSapApiQuery - SQL: " . substr($sql, 0, 100));
            error_log("🔷 executeSapApiQuery - forceRefresh: " . ($forceRefresh ? 'true' : 'false'));
            error_log("🔷 executeSapApiQuery - incremental: " . ($incremental ? 'true' : 'false'));
            error_log("🔷 executeSapApiQuery - cacheKey: " . $cacheKey);
            error_log("🔷 executeSapApiQuery - page: {$page}, perPage: {$perPage}");

            $existingCachedData = null;
            
            // Verificar se há cache e se não está forçando refresh
            if (!$forceRefresh) {
                $cached = $cacheService->get($cacheKey);
                if ($cached) {
                    error_log("✅ Cache encontrado para: " . $cacheKey);
                    $cachedPayload = $cached['data'];
                    
                    // Não retornar cache se for erro
                    if (isset($cachedPayload['success']) && $cachedPayload['success'] === false) {
                        error_log("⚠️ Cache contém erro, ignorando e fazendo nova consulta");
                        $cacheService->forget($cacheKey);
                    } else {
                        // IMPORTANTE: O cache contém TODOS os dados (não paginados)
                        $allData = $cachedPayload['data'] ?? [];
                        $totalRows = $cachedPayload['total_rows'] ?? count($allData);
                        
                        // Se busca incremental e há cache, usar cache existente como base
                        if ($incremental) {
                            $existingCachedData = $allData;
                            error_log("🔄 Modo incremental: usando cache existente como base (" . count($allData) . " registros)");
                        } else {
                            // Aplicar paginação apenas na leitura
                            $offset = max(0, ($page - 1) * $perPage);
                            $paginatedData = array_slice($allData, $offset, $perPage);
                            
                            error_log("✅ Cache: Total de registros: {$totalRows}, Página: {$page}, Mostrando: " . count($paginatedData));
                            
                            return [
                                'success' => true,
                                'data' => $paginatedData,
                                'rows_count' => count($paginatedData),
                                'total_rows' => $totalRows,
                                'execution_time' => (float)($cachedPayload['execution_time'] ?? 0.0),
                                'connection_type' => 'sap_api',
                                'sql' => $sql,
                                'query_mode' => 'custom_sql',
                                'cache' => [
                                    'from_cache' => true,
                                    'stored_at' => $cached['stored_at'],
                                    'cache_key' => $cacheKey
                                ]
                            ];
                        }
                    }
                } else {
                    error_log("ℹ️ Nenhum cache encontrado");
                }
            } else {
                // Se forçando refresh, verificar se há cache para usar como base em modo incremental
                if ($incremental) {
                    $cached = $cacheService->get($cacheKey);
                    if ($cached && isset($cached['data']['success']) && $cached['data']['success'] !== false) {
                        $existingCachedData = $cached['data']['data'] ?? [];
                        error_log("🔄 Modo incremental com refresh: mantendo cache existente como base (" . count($existingCachedData) . " registros)");
                        
                        // IMPORTANTE: Verificar o maior DocEntry no cache para debug
                        if (!empty($existingCachedData)) {
                            $incrementalInfo = $this->detectIncrementalField($existingCachedData);
                            if ($incrementalInfo && $incrementalInfo['type'] === 'numeric') {
                                $field = $incrementalInfo['field'];
                                $maxValue = null;
                                $sampleValues = [];
                                foreach ($existingCachedData as $row) {
                                    if (isset($row[$field])) {
                                        $value = (int)$row[$field];
                                        $sampleValues[] = $value;
                                        if ($maxValue === null || $value > $maxValue) {
                                            $maxValue = $value;
                                        }
                                    }
                                }
                                if ($maxValue !== null) {
                                    // Mostrar os 10 maiores valores para verificar
                                    rsort($sampleValues, SORT_NUMERIC);
                                    $topValues = array_slice($sampleValues, 0, 10);
                                    error_log("📊 Maior {$field} no cache: {$maxValue}");
                                    error_log("📊 Top 10 valores de {$field} no cache: " . implode(', ', $topValues));
                                }
                            }
                        }
                        
                        // Tentar otimizar a query SQL para buscar apenas novos registros
                        $optimizedSql = $this->optimizeSqlForIncremental($sql, $existingCachedData);
                        if ($optimizedSql !== $sql) {
                            error_log("⚡ Query otimizada para busca incremental");
                            error_log("📝 SQL original (primeiros 200 chars): " . substr($sql, 0, 200));
                            error_log("📝 SQL otimizado (primeiros 200 chars): " . substr($optimizedSql, 0, 200));
                            $sql = $optimizedSql;
                        } else {
                            error_log("⚠️ Não foi possível otimizar a query SQL. A busca incremental pode não ser eficiente.");
                        }
                    }
                } else {
                    error_log("🔄 Forçando refresh completo, limpando cache");
                    $cacheService->forget($cacheKey);
                }
            }
            
            // Antes de chamar a API, tentar otimizar a query automaticamente
            // ATENÇÃO: A otimização é muito conservadora para não quebrar queries válidas
            $sql = $this->optimizeQuerySql($sql);
            
            // Log da query final que será enviada (útil para debug)
            error_log("🔷 SQL final que será enviado à API (primeiros 500 chars): " . substr($sql, 0, 500));
            if ($incremental) {
                error_log("🔄 MODO INCREMENTAL: Query otimizada para buscar apenas registros novos");
                // Mostrar a parte final da query onde está a cláusula incremental
                $wherePos = stripos($sql, 'WHERE');
                if ($wherePos !== false) {
                    $whereClause = substr($sql, $wherePos, 200);
                    error_log("📝 Cláusula WHERE na query (últimos 200 chars): " . $whereClause);
                }
            }

            // Buscar dados da API
            error_log("📡 Chamando API SAP...");
            $startTime = microtime(true);
            try {
                $apiResult = $this->getSapApiService()->execute($sql);
                $executionTime = microtime(true) - $startTime;
                $rowsCount = isset($apiResult['rows_count']) ? $apiResult['rows_count'] : (isset($apiResult['data']) ? count($apiResult['data']) : 0);
                error_log("✅ API SAP retornou: {$rowsCount} registros em " . round($executionTime, 2) . "s");
                
                // Se modo incremental e retornou 0 registros, avisar
                if ($incremental && $rowsCount == 0) {
                    error_log("ℹ️ API retornou 0 registros. Isso pode significar que não há registros novos no banco de dados.");
                }
            } catch (\Exception $e) {
                error_log("❌ ERRO ao chamar API SAP: " . $e->getMessage());
                error_log("❌ Arquivo: " . $e->getFile() . ":" . $e->getLine());
                throw $e;
            }
            
            // Converter encoding e obter TODOS os dados (sem paginação)
            $newData = $this->convertEncodingToUtf8($apiResult['data']);

            // Limitar resultado bruto para evitar travar consultas gigantes (opcional)
            // Use a env SAP_REPORT_API_MAX_ROWS (> 0) para ativar o truncamento.
            // Se não definido ou <= 0, nenhum limite é aplicado.
            $maxRows = isset($_ENV['SAP_REPORT_API_MAX_ROWS']) ? (int)$_ENV['SAP_REPORT_API_MAX_ROWS'] : 0;
            if ($maxRows > 0 && is_array($newData) && count($newData) > $maxRows) {
                error_log("⚠️ executeSapApiQuery - Resultado da API com " . count($newData) . " linhas, truncando para {$maxRows} para evitar travamentos.");
                $newData = array_slice($newData, 0, $maxRows);
            }
            
            // Se modo incremental e há cache existente, mesclar apenas novos registros
            if ($incremental && $existingCachedData !== null && !empty($existingCachedData)) {
                error_log("🔄 Modo incremental ativado");
                error_log("📊 Cache existente: " . count($existingCachedData) . " registros");
                error_log("📊 Dados retornados da API: " . count($newData) . " registros");
                
                // Mostrar alguns exemplos dos dados retornados para debug
                if (!empty($newData)) {
                    $sampleRow = $newData[0];
                    $sampleKeys = array_keys($sampleRow);
                    error_log("📋 Exemplo de registro retornado (primeiros campos): " . json_encode(array_slice($sampleRow, 0, 5)));
                }
                
                // Verificar se a otimização SQL funcionou (se retornou menos dados que o cache)
                // Se retornou mais ou similar, a otimização pode não ter funcionado
                if (count($newData) >= count($existingCachedData) * 0.8) {
                    error_log("⚠️ A otimização SQL pode não ter funcionado (retornou muitos dados: " . count($newData) . " vs cache: " . count($existingCachedData) . "). Desabilitando mesclagem incremental para evitar lentidão.");
                    // Se a otimização não funcionou, usar apenas os novos dados (mais rápido)
                    $allData = $newData;
                } else {
                    // Otimização funcionou, mesclar apenas novos
                    error_log("✅ Otimização SQL funcionou (retornou " . count($newData) . " dados vs " . count($existingCachedData) . " no cache). Mesclando...");
                    
                    // IMPORTANTE: Como a query SQL já foi otimizada (WHERE DocEntry > X),
                    // todos os registros retornados pela API são novos. Não precisamos
                    // fazer verificação dupla na mesclagem - apenas adicionar ao cache existente.
                    $mergeStart = microtime(true);
                    
                    // Se a query foi otimizada, confiar nos dados retornados pela API
                    // e apenas mesclar com os existentes (sem filtrar novamente)
                    if (!empty($newData)) {
                        // Verificar se realmente são novos (apenas para log/debug)
                        $incrementalInfo = $this->detectIncrementalField($existingCachedData);
                        if ($incrementalInfo && $incrementalInfo['type'] === 'numeric') {
                            $field = $incrementalInfo['field'];
                            $latestValue = null;
                            foreach ($existingCachedData as $row) {
                                if (isset($row[$field])) {
                                    $value = (int)$row[$field];
                                    if ($latestValue === null || $value > $latestValue) {
                                        $latestValue = $value;
                                    }
                                }
                            }
                            
                            // Contar quantos são realmente novos (apenas para log)
                            $trulyNewCount = 0;
                            foreach ($newData as $row) {
                                if (isset($row[$field]) && (int)$row[$field] > $latestValue) {
                                    $trulyNewCount++;
                                }
                            }
                            
                            if ($trulyNewCount < count($newData)) {
                                error_log("⚠️ ATENÇÃO: Alguns registros retornados pela API não são maiores que {$latestValue}. Isso pode indicar um problema na otimização SQL.");
                            } else {
                                error_log("✅ Todos os " . count($newData) . " registros retornados são novos (maiores que {$latestValue})");
                            }
                        }
                        
                        // Mesclar: adicionar novos ao final (a query SQL já garantiu que são novos)
                        // IMPORTANTE: Como a query tem ORDER BY DocDate DESC, CreateTS DESC, DocNum DESC,
                        // os novos registros (com DocEntry maior) devem aparecer no TOPO.
                        // Por isso, adicionamos os novos ANTES dos existentes para manter a ordem correta.
                        $allData = array_merge($newData, $existingCachedData);
                        
                        error_log("📊 Ordem dos dados após mesclagem: " . count($newData) . " novos (no topo) + " . count($existingCachedData) . " existentes");
                    } else {
                        // Se não retornou dados novos, manter apenas os existentes
                        $allData = $existingCachedData;
                        error_log("ℹ️ API não retornou registros novos. Mantendo cache existente.");
                    }
                    
                    $mergeTime = microtime(true) - $mergeStart;
                    error_log("✅ Dados mesclados: " . count($existingCachedData) . " existentes + " . count($newData) . " novos = " . count($allData) . " total (tempo: " . round($mergeTime, 3) . "s)");
                    
                    // Se não houve novos registros, avisar
                    if (count($allData) == count($existingCachedData)) {
                        error_log("ℹ️ Nenhum registro novo foi adicionado. Total permanece: " . count($allData));
                    } else {
                        // Mostrar exemplos dos novos registros adicionados
                        $newCount = count($allData) - count($existingCachedData);
                        error_log("🎉 {$newCount} novos registros foram adicionados ao cache!");
                        
                        // Mostrar o primeiro e último DocEntry dos novos registros (se disponível)
                        if (!empty($newData)) {
                            $incrementalInfo = $this->detectIncrementalField($newData);
                            if ($incrementalInfo && isset($newData[0][$incrementalInfo['field']])) {
                                $firstNew = $newData[0][$incrementalInfo['field']];
                                $lastNew = end($newData)[$incrementalInfo['field']] ?? $firstNew;
                                error_log("📊 Novos registros: {$incrementalInfo['field']} de {$firstNew} até {$lastNew}");
                            }
                        }
                    }
                }
            } else {
                $allData = $newData;
            }
            
            $totalRows = count($allData);
            
            // IMPORTANTE: Salvar TODOS os dados no cache (não paginados)
            $cachePayload = [
                'success' => true,
                'data' => $allData, // TODOS os dados, não paginados
                'rows_count' => $totalRows,
                'total_rows' => $totalRows,
                'execution_time' => (float)($apiResult['execution_time'] ?? $executionTime),
                'connection_type' => 'sap_api',
                'sql' => $sql,
                'query_mode' => 'custom_sql'
            ];
            
            error_log("💾 Salvando no cache: {$totalRows} registros (todos os dados)");
            error_log("💾 Cache key: {$cacheKey}");
            $cacheSaved = $cacheService->put($cacheKey, $cachePayload);
            if ($cacheSaved) {
                error_log("✅ Cache salvo com sucesso!");
            } else {
                error_log("❌ ERRO ao salvar cache!");
            }
            
            // Aplicar paginação apenas para retorno (não salvar paginado)
            $offset = max(0, ($page - 1) * $perPage);
            $paginatedData = array_slice($allData, $offset, $perPage);
            
            $result = [
                'success' => true,
                'data' => $paginatedData,
                'rows_count' => count($paginatedData),
                'total_rows' => $totalRows,
                'execution_time' => (float)($apiResult['execution_time'] ?? $executionTime),
                'connection_type' => 'sap_api',
                'sql' => $sql,
                'query_mode' => 'custom_sql',
                'cache' => [
                    'from_cache' => false,
                    'stored_at' => time(),
                    'cache_key' => $cacheKey,
                    'incremental' => $incremental
                ]
            ];

            error_log("✅ executeSapApiQuery - SUCESSO - Retornando " . count($result['data'] ?? []) . " registros da página {$page} de {$totalRows} total");
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

    /**
     * Busca incremental: mescla novos registros do cache com dados existentes
     * Baseado em um campo de data/timestamp para identificar registros novos
     */
    private function mergeIncrementalData(array $existingData, array $newData, ?string $dateField = null): array
    {
        if (empty($existingData)) {
            return $newData;
        }
        
        if (empty($newData)) {
            return $existingData;
        }
        
        // Detectar melhor campo incremental (DocEntry, ID, ou data)
        $incrementalInfo = $this->detectIncrementalField($existingData);
        if ($incrementalInfo === null) {
            error_log("⚠️ Não foi possível detectar campo incremental para mesclagem. Retornando apenas novos dados.");
            return $newData;
        }
        
        $field = $incrementalInfo['field'];
        $type = $incrementalInfo['type'];
        
        error_log("🔄 Mesclando usando campo: {$field} (tipo: {$type})");
        
        // Encontrar o valor mais recente baseado no tipo
        $latestValue = null;
        $trulyNew = [];
        
        if ($type === 'numeric') {
            // Para DocEntry/ID: encontrar o maior valor numérico
            foreach ($existingData as $row) {
                if (isset($row[$field])) {
                    $value = (int)$row[$field];
                    if ($latestValue === null || $value > $latestValue) {
                        $latestValue = $value;
                    }
                }
            }
            
            if ($latestValue === null) {
                error_log("⚠️ Não foi possível encontrar {$field} mais recente");
                return $newData;
            }
            
            error_log("📊 {$field} mais recente no cache: {$latestValue}");
            
            // Filtrar apenas registros com ID maior
            $skippedCount = 0;
            $sampleSkipped = [];
            foreach ($newData as $row) {
                if (isset($row[$field])) {
                    $value = (int)$row[$field];
                    if ($value > $latestValue) {
                        $trulyNew[] = $row;
                    } else {
                        $skippedCount++;
                        // Guardar exemplos dos primeiros 3 registros ignorados
                        if ($skippedCount <= 3) {
                            $sampleSkipped[] = $value;
                        }
                    }
                } else {
                    error_log("⚠️ Registro sem campo {$field}");
                }
            }
            
            error_log("📊 Registros filtrados: " . count($newData) . " retornados da API, " . count($trulyNew) . " são realmente novos (maiores que {$latestValue})");
            if ($skippedCount > 0) {
                error_log("⏭️ {$skippedCount} registros ignorados (não são maiores que {$latestValue}). Exemplos: " . implode(', ', $sampleSkipped));
            }
            
            // Se não encontrou novos, mostrar alguns exemplos dos dados retornados
            if (empty($trulyNew) && !empty($newData)) {
                $sampleRow = $newData[0];
                $sampleValue = isset($sampleRow[$field]) ? (int)$sampleRow[$field] : 'N/A';
                error_log("⚠️ Nenhum registro novo encontrado. Exemplo do primeiro registro retornado: {$field}=" . $sampleValue . " (último no cache: {$latestValue})");
                error_log("⚠️ Todos os registros retornados têm {$field} <= {$latestValue}. Verifique se há registros realmente novos no banco de dados.");
            }
            
        } elseif ($type === 'timestamp' && isset($incrementalInfo['secondary'])) {
            // Para DocDate + CreateTS: usar ambos para timestamp completo
            $secondaryField = $incrementalInfo['secondary'];
            $latestTimestamp = null;
            $latestCreateTS = null;
            
            foreach ($existingData as $row) {
                if (isset($row[$field]) && isset($row[$secondaryField])) {
                    $docDate = $this->parseDate($row[$field]);
                    $createTS = (int)($row[$secondaryField] ?? 0);
                    
                    if ($docDate && ($latestTimestamp === null || $docDate > $latestTimestamp || 
                        ($docDate == $latestTimestamp && $createTS > $latestCreateTS))) {
                        $latestTimestamp = $docDate;
                        $latestCreateTS = $createTS;
                    }
                }
            }
            
            if ($latestTimestamp === null) {
                error_log("⚠️ Não foi possível encontrar timestamp completo");
                return $newData;
            }
            
            error_log("📅 Timestamp completo mais recente: " . date('Y-m-d H:i:s', $latestTimestamp) . " + CreateTS={$latestCreateTS}");
            
            // Filtrar registros com timestamp maior
            foreach ($newData as $row) {
                if (isset($row[$field]) && isset($row[$secondaryField])) {
                    $docDate = $this->parseDate($row[$field]);
                    $createTS = (int)($row[$secondaryField] ?? 0);
                    
                    if ($docDate && ($docDate > $latestTimestamp || 
                        ($docDate == $latestTimestamp && $createTS > $latestCreateTS))) {
                        $trulyNew[] = $row;
                    }
                }
            }
            
        } else {
            // Para campos de data simples: usar timestamp completo
            $latestTimestamp = null;
            $latestDateValue = null;
            
            foreach ($existingData as $row) {
                if (isset($row[$field])) {
                    $rowTimestamp = $this->parseDate($row[$field]);
                    if ($rowTimestamp && ($latestTimestamp === null || $rowTimestamp > $latestTimestamp)) {
                        $latestTimestamp = $rowTimestamp;
                        $latestDateValue = $row[$field];
                    }
                }
            }
            
            if ($latestTimestamp === null) {
                error_log("⚠️ Não foi possível encontrar timestamp mais recente");
                return $newData;
            }
            
            error_log("📅 Timestamp mais recente no cache: {$latestDateValue} (" . date('Y-m-d H:i:s', $latestTimestamp) . ")");
            
            // Filtrar apenas registros novos (com timestamp maior)
            foreach ($newData as $row) {
                if (isset($row[$field])) {
                    $rowTimestamp = $this->parseDate($row[$field]);
                    if ($rowTimestamp && $rowTimestamp > $latestTimestamp) {
                        $trulyNew[] = $row;
                    }
                }
            }
        }
        
        error_log("📊 Registros filtrados: " . count($newData) . " retornados da API, " . count($trulyNew) . " são realmente novos");
        
        if (empty($trulyNew)) {
            error_log("ℹ️ Nenhum registro novo encontrado. Mantendo cache existente.");
            return $existingData;
        }
        
        // Mesclar: adicionar novos ao final
        $merged = array_merge($existingData, $trulyNew);
        
        // Só ordenar se houver poucos dados (menos de 10k registros) para não impactar performance
        if (count($merged) < 10000 && $type !== 'numeric') {
            // Ordenar apenas se não for numérico (DocEntry/ID já vem ordenado)
            usort($merged, function($a, $b) use ($field, $type) {
                if ($type === 'numeric') {
                    $valA = (int)($a[$field] ?? 0);
                    $valB = (int)($b[$field] ?? 0);
                    return $valB <=> $valA; // Descendente
                } else {
                    $dateA = $this->parseDate($a[$field] ?? null);
                    $dateB = $this->parseDate($b[$field] ?? null);
                    if ($dateA === null) return 1;
                    if ($dateB === null) return -1;
                    return $dateB <=> $dateA; // Descendente
                }
            });
            error_log("✅ Busca incremental: " . count($existingData) . " existentes + " . count($trulyNew) . " novos = " . count($merged) . " total (ordenado)");
        } else {
            error_log("✅ Busca incremental: " . count($existingData) . " existentes + " . count($trulyNew) . " novos = " . count($merged) . " total");
        }
        
        return $merged;
    }
    
    /**
     * Detecta automaticamente o melhor campo para busca incremental
     * Prioriza: DocEntry (documentos) > ID (cadastros) > DocDate (data+hora) > outros campos de data
     */
    private function detectIncrementalField(array $data): ?array
    {
        if (empty($data)) {
            return null;
        }
        
        $firstRow = $data[0];
        
        // 1. PRIORIDADE MÁXIMA: DocDate + CreateTS para documentos (melhor para queries com JOINs)
        // IMPORTANTE: Como a query tem JOINs (especialmente com INV1), um mesmo DocEntry pode aparecer
        // múltiplas vezes (uma linha para cada item do documento). Usar data/hora garante que
        // pegamos TODOS os registros criados após uma data/hora específica, mesmo que o DocEntry se repita.
        if (isset($firstRow['DocDate']) || isset($firstRow['DataCriacao'])) {
            $docDateField = isset($firstRow['DocDate']) ? 'DocDate' : 'DataCriacao';
            $createTSField = isset($firstRow['CreateTS']) ? 'CreateTS' : (isset($firstRow['HoraCriação']) ? 'HoraCriação' : null);
            
            if ($createTSField) {
                error_log("✅ Campo incremental detectado: {$docDateField} + {$createTSField} (timestamp completo - IDEAL para documentos com JOINs)");
                return [
                    'field' => $docDateField,
                    'type' => 'timestamp',
                    'comparison' => '>',
                    'secondary' => $createTSField
                ];
            }
            
            // Se não tem CreateTS, usar apenas DocDate
            error_log("✅ Campo incremental detectado: {$docDateField} (documento - sem CreateTS)");
            return ['field' => $docDateField, 'type' => 'date', 'comparison' => '>'];
        }
        
        // 2. Fallback: DocEntry para documentos SAP B1 (quando não há data disponível)
        // ATENÇÃO: DocEntry pode se repetir devido a JOINs, então não é ideal
        if (isset($firstRow['DocEntry'])) {
            error_log("⚠️ Campo incremental detectado: DocEntry (documento SAP B1 - pode se repetir devido a JOINs)");
            return ['field' => 'DocEntry', 'type' => 'numeric', 'comparison' => '>'];
        }
        
        // 3. Priorizar ID para cadastros (número sequencial, não se repete)
        if (isset($firstRow['id']) || isset($firstRow['ID']) || isset($firstRow['Id'])) {
            $idField = isset($firstRow['id']) ? 'id' : (isset($firstRow['ID']) ? 'ID' : 'Id');
            error_log("✅ Campo incremental detectado: {$idField} (cadastro)");
            return ['field' => $idField, 'type' => 'numeric', 'comparison' => '>'];
        }
        
        // 5. Outros campos de data comuns
        $dateFieldCandidates = ['CreateDate', 'UpdateDate', 'DocDueDate', 'TaxDate', 
                               'created_at', 'updated_at', 'date', 'data'];
        
        foreach ($dateFieldCandidates as $candidate) {
            if (isset($firstRow[$candidate])) {
                error_log("✅ Campo incremental detectado: {$candidate} (data)");
                return ['field' => $candidate, 'type' => 'date', 'comparison' => '>'];
            }
        }
        
        // 6. Tentar encontrar qualquer campo que pareça ser data
        foreach (array_keys($firstRow) as $field) {
            if (stripos($field, 'date') !== false || stripos($field, 'data') !== false || 
                stripos($field, 'time') !== false || stripos($field, 'created') !== false || 
                stripos($field, 'updated') !== false) {
                error_log("✅ Campo incremental detectado (por padrão): {$field}");
                return ['field' => $field, 'type' => 'date', 'comparison' => '>'];
            }
        }
        
        error_log("⚠️ Nenhum campo adequado para busca incremental detectado");
        return null;
    }
    
    /**
     * Detecta automaticamente campo de data/timestamp nos dados (método legado)
     * Mantido para compatibilidade
     */
    private function detectDateField(array $data): ?string
    {
        $incremental = $this->detectIncrementalField($data);
        return $incremental ? $incremental['field'] : null;
    }
    
    /**
     * Converte string de data/timestamp para timestamp Unix para comparação precisa
     * Suporta formatos: YYYY-MM-DD, YYYY-MM-DDTHH:MM:SS, DD/MM/YYYY, etc.
     */
    private function parseDate($dateValue): ?int
    {
        if ($dateValue === null || $dateValue === '') {
            return null;
        }
        
        // Se já for timestamp numérico
        if (is_numeric($dateValue)) {
            return (int)$dateValue;
        }
        
        // Se estiver no formato DD/MM/YYYY (comum em DataCriacao do SAP)
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateValue, $matches)) {
            try {
                $day = (int)$matches[1];
                $month = (int)$matches[2];
                $year = (int)$matches[3];
                $dt = new \DateTime("{$year}-{$month}-{$day}");
                return $dt->getTimestamp();
            } catch (\Exception $e) {
                error_log("⚠️ Erro ao parsear data DD/MM/YYYY: {$dateValue} - " . $e->getMessage());
            }
        }
        
        // Se estiver no formato ISO 8601 (YYYY-MM-DDTHH:MM:SS ou YYYY-MM-DDTHH:MM:SS.xxx)
        if (preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})/', $dateValue, $matches)) {
            // Usar DateTime para parsear com precisão (preserva hora, minutos, segundos)
            try {
                $dt = new \DateTime($dateValue);
                return $dt->getTimestamp();
            } catch (\Exception $e) {
                error_log("⚠️ Erro ao parsear data ISO: {$dateValue} - " . $e->getMessage());
            }
        }
        
        // Tentar parsear como data/timestamp (strtotime pode lidar com vários formatos)
        $timestamp = strtotime($dateValue);
        if ($timestamp === false) {
            error_log("⚠️ Não foi possível parsear data: {$dateValue}");
            return null;
        }
        
        return $timestamp;
    }
    
    /**
     * Otimiza a query SQL para buscar apenas novos registros quando em modo incremental
     * Estratégia inteligente baseada no tipo de campo:
     * - DocEntry/ID: Usa comparação numérica (>)
     * - DocDate + CreateTS: Usa timestamp completo
     * - DocDate: Usa data + hora se disponível
     */
    private function optimizeSqlForIncremental(string $sql, array $existingData): string
    {
        if (empty($existingData)) {
            return $sql;
        }
        
        // Detectar melhor campo incremental (DocEntry, ID, ou data)
        $incrementalInfo = $this->detectIncrementalField($existingData);
        if ($incrementalInfo === null) {
            error_log("⚠️ Não foi possível detectar campo incremental para otimização SQL");
            return $sql;
        }
        
        $field = $incrementalInfo['field'];
        $type = $incrementalInfo['type'];
        $comparison = $incrementalInfo['comparison'] ?? '>';
        
        error_log("📅 Usando campo incremental: {$field} (tipo: {$type}) para busca incremental");
        
        // Detectar alias da tabela principal que contém este campo na query SQL
        $tableAlias = $this->detectTableAliasForField($sql, $field);
        $qualifiedField = $tableAlias ? "{$tableAlias}.\"{$field}\"" : "\"{$field}\"";
        
        error_log("📊 Alias da tabela detectado para {$field}: " . ($tableAlias ?: 'nenhum (usando campo direto)'));
        
        // Encontrar o valor mais recente baseado no tipo de campo
        $latestValue = null;
        $latestRow = null;
        
        if ($type === 'numeric') {
            // Para DocEntry/ID: encontrar o maior valor numérico
            // IMPORTANTE: Como a query tem JOINs (especialmente com INV1), um mesmo DocEntry
            // pode aparecer múltiplas vezes (uma vez para cada linha do documento).
            // Precisamos encontrar o maior DocEntry ÚNICO, não apenas o maior valor em todas as linhas.
            $uniqueValues = [];
            $sampleValues = [];
            foreach ($existingData as $row) {
                if (isset($row[$field])) {
                    $value = (int)$row[$field];
                    $uniqueValues[$value] = true; // Usar array associativo para valores únicos
                    $sampleValues[] = $value;
                    if ($latestValue === null || $value > $latestValue) {
                        $latestValue = $value;
                        $latestRow = $row;
                    }
                }
            }
            
            if ($latestValue === null) {
                error_log("⚠️ Não foi possível encontrar {$field} mais recente para otimização SQL incremental");
                return $sql;
            }
            
            // Contar quantos DocEntry únicos existem
            $uniqueCount = count($uniqueValues);
            $totalRows = count($existingData);
            error_log("📊 Total de registros no cache: {$totalRows}");
            error_log("📊 Total de {$field} únicos no cache: {$uniqueCount}");
            
            // Mostrar alguns exemplos dos valores encontrados para debug
            $uniqueValuesArray = array_keys($uniqueValues);
            rsort($uniqueValuesArray, SORT_NUMERIC);
            $topValues = array_slice($uniqueValuesArray, 0, 10);
            error_log("📊 Top 10 valores únicos de {$field} no cache: " . implode(', ', $topValues));
            error_log("📊 {$field} mais recente encontrado no cache: {$latestValue}");
            
            // IMPORTANTE: Usar >= ao invés de > para garantir que pegamos todos os registros
            // que podem ter sido inseridos no mesmo momento ou logo após
            // Mas como estamos usando DocEntry que é sequencial, > deve funcionar
            // Vamos usar > mas adicionar um log para verificar
            error_log("📝 Cláusula WHERE a ser adicionada: {$qualifiedField} > {$latestValue}");
            error_log("📝 Isso deve retornar todos os registros com {$field} maior que {$latestValue}");
            
            // Construir cláusula WHERE com comparação numérica (usando alias se detectado)
            $whereClause = "{$qualifiedField} > {$latestValue}";
            
        } elseif ($type === 'timestamp' && isset($incrementalInfo['secondary'])) {
            // Para DocDate + CreateTS: usar ambos para timestamp completo
            $secondaryField = $incrementalInfo['secondary'];
            $latestTimestamp = null;
            $latestDocDate = null;
            $latestCreateTS = null;
            
            foreach ($existingData as $row) {
                if (isset($row[$field]) && isset($row[$secondaryField])) {
                    $docDate = $this->parseDate($row[$field]);
                    $createTS = (int)($row[$secondaryField] ?? 0);
                    
                    // Combinar DocDate + CreateTS para timestamp completo
                    if ($docDate && ($latestTimestamp === null || $docDate > $latestTimestamp || 
                        ($docDate == $latestTimestamp && $createTS > $latestCreateTS))) {
                        $latestTimestamp = $docDate;
                        $latestDocDate = $row[$field];
                        $latestCreateTS = $createTS;
                    }
                }
            }
            
            if ($latestTimestamp === null) {
                error_log("⚠️ Não foi possível encontrar timestamp completo para otimização SQL incremental");
                return $sql;
            }
            
            error_log("📅 Timestamp completo mais recente: DocDate={$latestDocDate}, CreateTS={$latestCreateTS}");
            
            // IMPORTANTE: Se o campo é "DataCriacao" (que vem de TO_VARCHAR), precisamos usar o campo original "DocDate" na query SQL
            // O campo "DataCriacao" é apenas um alias formatado, não existe na tabela
            // Também, "HoraCriação" é um alias, o campo original é "CreateTS"
            $sqlField = ($field === 'DataCriacao') ? 'DocDate' : $field;
            $sqlSecondaryField = ($secondaryField === 'HoraCriação') ? 'CreateTS' : $secondaryField;
            
            // Construir cláusula WHERE combinando DocDate e CreateTS (usando alias se detectado)
            // Procurar pelo campo original na query SQL (DocDate, não DataCriacao)
            $tableAlias = $this->detectTableAliasForField($sql, $sqlField);
            if (!$tableAlias) {
                // Se não encontrou alias, tentar detectar pela tabela principal (geralmente T0)
                // Procurar por padrão T0."DocDate" ou T0.DocDate na query
                if (preg_match('/([T\d]+)\.\s*["\']?DocDate["\']?/i', $sql, $matches)) {
                    $tableAlias = $matches[1];
                    error_log("📊 Alias detectado por padrão DocDate: {$tableAlias}");
                } else {
                    $tableAlias = 'T0'; // Fallback para T0 (tabela principal)
                    error_log("📊 Usando alias padrão: {$tableAlias}");
                }
            }
            
            $qualifiedField = "{$tableAlias}.\"{$sqlField}\"";
            $qualifiedSecondary = "{$tableAlias}.\"{$sqlSecondaryField}\"";
            
            error_log("📊 Campos SQL: {$qualifiedField} e {$qualifiedSecondary}");
            
            // Se DataCriacao está em formato DD/MM/YYYY, precisamos parsear e converter para formato SQL
            // Caso contrário, usar o valor direto
            if ($field === 'DataCriacao' && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $latestDocDate)) {
                // Converter DD/MM/YYYY para YYYY-MM-DD para SQL
                $dateParts = explode('/', $latestDocDate);
                if (count($dateParts) === 3) {
                    $sqlDate = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0]; // YYYY-MM-DD
                    error_log("📅 DataCriacao convertida de {$latestDocDate} para {$sqlDate}");
                } else {
                    $sqlDate = $this->formatDateForSql($latestDocDate);
                }
            } else {
                $sqlDate = $this->formatDateForSql($latestDocDate);
            }
            
            if ($sqlDate === null) {
                error_log("⚠️ Não foi possível formatar DocDate para SQL");
                return $sql;
            }
            
            // WHERE (DocDate > 'data') OR (DocDate = 'data' AND CreateTS > timestamp)
            // IMPORTANTE: Adicionar parênteses para garantir precedência correta
            // IMPORTANTE: A cláusula completa precisa estar entre parênteses para não quebrar a precedência do AND anterior
            $whereClause = "( ({$qualifiedField} > '{$sqlDate}') OR ({$qualifiedField} = '{$sqlDate}' AND {$qualifiedSecondary} > {$latestCreateTS}) )";
            error_log("📝 Cláusula WHERE a ser adicionada: {$whereClause}");
            error_log("📝 Campos qualificados: {$qualifiedField} e {$qualifiedSecondary}");
            
        } else {
            // Para campos de data simples: usar timestamp completo
            $latestTimestamp = null;
            $latestDateValue = null;
            
            foreach ($existingData as $row) {
                if (isset($row[$field])) {
                    $rowTimestamp = $this->parseDate($row[$field]);
                    if ($rowTimestamp && ($latestTimestamp === null || $rowTimestamp > $latestTimestamp)) {
                        $latestTimestamp = $rowTimestamp;
                        $latestDateValue = $row[$field];
                    }
                }
            }
            
            if ($latestTimestamp === null || $latestDateValue === null) {
                error_log("⚠️ Não foi possível encontrar timestamp mais recente para otimização SQL incremental");
                return $sql;
            }
            
            error_log("📅 Timestamp mais recente encontrado: {$latestDateValue} (" . date('Y-m-d H:i:s', $latestTimestamp) . ")");
            
            $sqlDate = $this->formatDateForSql($latestDateValue);
            if ($sqlDate === null) {
                error_log("⚠️ Não foi possível formatar timestamp para SQL: " . $latestDateValue);
                return $sql;
            }
            
            // Usar alias se detectado
            $tableAlias = $this->detectTableAliasForField($sql, $field);
            $qualifiedField = $tableAlias ? "{$tableAlias}.\"{$field}\"" : "\"{$field}\"";
            $whereClause = "{$qualifiedField} > '{$sqlDate}'";
        }
        
        // Verificar se já existe WHERE na query (detecção mais robusta)
        $sqlUpper = strtoupper($sql);
        // Procurar por WHERE que não esteja dentro de strings ou comentários
        $hasWhere = preg_match('/\bWHERE\b/i', $sql) !== false;
        
        error_log("📝 Cláusula a adicionar: {$whereClause}");
        error_log("📝 Query já tem WHERE: " . ($hasWhere ? 'SIM' : 'NÃO'));
        
        // IMPORTANTE: Sempre usar AND se já existe WHERE, nunca adicionar WHERE duplicado
        $connector = $hasWhere ? ' AND ' : ' WHERE ';
        
        // Tentar encontrar uma posição segura para adicionar a cláusula
        // Procurar por ORDER BY, GROUP BY, HAVING ou LIMIT (em ordem de prioridade)
        $patterns = [
            '/(\s+ORDER\s+BY\s+[^;]*)/i',
            '/(\s+GROUP\s+BY\s+[^;]*)/i',
            '/(\s+HAVING\s+[^;]*)/i',
            '/(\s+LIMIT\s+[^;]*)/i'
        ];
        
        $added = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sql, $matches, PREG_OFFSET_CAPTURE)) {
                $position = $matches[0][1];
                $before = substr($sql, 0, $position);
                $after = substr($sql, $position);
                
                // Sempre usar o conector correto (AND se já tem WHERE, WHERE se não tem)
                $sql = $before . $connector . $whereClause . $after;
                $added = true;
                error_log("✅ Cláusula incremental adicionada antes de: " . trim($matches[0][0]) . " (usando: " . trim($connector) . ")");
                break;
            }
        }
        
        // Se não encontrou nenhuma cláusula, adicionar no final ANTES de qualquer ponto e vírgula
        if (!$added) {
            // Remover ponto e vírgula final se existir, adicionar cláusula, depois recolocar
            if (substr(trim($sql), -1) === ';') {
                $sql = rtrim($sql, ';') . $connector . $whereClause . ';';
            } else {
                $sql .= $connector . $whereClause;
            }
            error_log("✅ Cláusula incremental adicionada no final (usando: " . trim($connector) . ")");
        }
        
        // Validação final: garantir que não há WHERE duplicado
        $whereCount = preg_match_all('/\bWHERE\b/i', $sql);
        if ($whereCount > 1) {
            error_log("❌ ERRO CRÍTICO: Múltiplos WHERE detectados na query! Contagem: {$whereCount}");
            error_log("❌ Query problemática (primeiros 800 chars): " . substr($sql, 0, 800));
            // Tentar corrigir: substituir todos os WHERE exceto o primeiro por AND
            $parts = preg_split('/\bWHERE\b/i', $sql, 2);
            if (count($parts) === 2) {
                $sql = $parts[0] . ' WHERE ' . preg_replace('/\bWHERE\b/i', ' AND ', $parts[1]);
                error_log("⚠️ Correção aplicada: removidos WHERE duplicados");
            } else {
                error_log("❌ Não foi possível corrigir automaticamente. Retornando query original.");
                return $sql; // Retornar query original se não conseguir corrigir
            }
        }
        
        error_log("⚡ Query otimizada: adicionada cláusula {$whereClause}");
        error_log("📝 Query final (primeiros 500 chars): " . substr($sql, 0, 500));
        error_log("📝 Query final completa (últimos 200 chars): " . substr($sql, -200));
        
        // Log adicional: verificar se a cláusula foi realmente adicionada
        if (stripos($sql, $whereClause) === false) {
            error_log("❌ ERRO: A cláusula incremental não foi encontrada na query final!");
        } else {
            error_log("✅ Cláusula incremental confirmada na query final");
        }
        
        // Verificar se há outros filtros que possam estar limitando os resultados
        // Por exemplo, se há um filtro de data que pode estar excluindo registros novos
        if (preg_match('/WHERE\s+.*?(\bDocDate\b|\bCreateDate\b|\bDataCriacao\b).*?>/i', $sql, $matches)) {
            error_log("⚠️ ATENÇÃO: Há um filtro de data na query que pode estar limitando os resultados!");
            error_log("⚠️ Verifique se o filtro de data não está excluindo registros novos.");
        }
        
        return $sql;
    }
    
    /**
     * Detecta o alias da tabela principal que contém um campo específico na query SQL
     * Exemplo: Para "DocEntry", procura por T0."DocEntry", T1."DocEntry", etc.
     * Retorna o alias mais comum (geralmente T0 para tabelas principais)
     */
    private function detectTableAliasForField(string $sql, string $field): ?string
    {
        // Procurar por padrões como T0."DocEntry", T1."DocEntry", etc.
        // Escapar o nome do campo para regex
        $fieldEscaped = preg_quote($field, '/');
        
        // Padrão: alias."Campo" ou alias.Campo (com ou sem aspas)
        $pattern = '/([T\d]+)\.\s*["\']?' . $fieldEscaped . '["\']?/i';
        
        if (preg_match_all($pattern, $sql, $matches)) {
            // Contar frequência de cada alias
            $aliasCounts = array_count_values($matches[1]);
            // Retornar o alias mais comum (geralmente T0 para tabela principal)
            arsort($aliasCounts);
            $mostCommonAlias = array_key_first($aliasCounts);
            
            error_log("📊 Alias detectado para {$field}: {$mostCommonAlias} (aparece " . $aliasCounts[$mostCommonAlias] . " vezes)");
            return $mostCommonAlias;
        }
        
        // Se não encontrou, verificar se o campo aparece sem alias (pode ser de uma única tabela)
        // Nesse caso, não usar alias
        if (stripos($sql, "\"{$field}\"") !== false || stripos($sql, "'{$field}'") !== false) {
            error_log("📊 Campo {$field} encontrado sem alias explícito na query");
            return null;
        }
        
        error_log("⚠️ Não foi possível detectar alias para {$field}");
        return null;
    }
    
    /**
     * Formata valor de data/timestamp para uso em SQL SAP
     * Preserva hora, minutos e segundos para comparação precisa
     */
    private function formatDateForSql($dateValue): ?string
    {
        if ($dateValue === null || $dateValue === '') {
            return null;
        }
        
        // Se já está em formato SQL com timestamp completo (YYYY-MM-DDTHH:MM:SS)
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $dateValue)) {
            // Retornar timestamp completo para comparação precisa
            return substr($dateValue, 0, 19); // YYYY-MM-DDTHH:MM:SS
        }
        
        // Se está em formato SQL apenas com data (YYYY-MM-DD)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
            // Se só tem data, adicionar hora 00:00:00 para comparação
            return $dateValue . 'T00:00:00';
        }
        
        // Tentar parsear e formatar com timestamp completo
        $timestamp = $this->parseDate($dateValue);
        if ($timestamp === null) {
            return null;
        }
        
        // Retornar timestamp completo: YYYY-MM-DDTHH:MM:SS
        return date('Y-m-d\TH:i:s', $timestamp);
    }
    
    /**
     * Otimiza queries SQL automaticamente para melhor performance
     * Adiciona sugestões e melhorias quando apropriado
     * 
     * ATENÇÃO: Esta função deve ser muito conservadora para não quebrar queries válidas
     */
    private function optimizeQuerySql(string $sql): string
    {
        $originalSql = $sql;
        $sqlUpper = strtoupper($sql);
        
        // 1. Verificar se há campos de data no SELECT que podem ser usados para filtro
        // Se não há WHERE com data, mas há campo de data no SELECT, sugerir filtro
        // (mas não vamos adicionar automaticamente, apenas logar sugestão)
        if (stripos($sqlUpper, 'DOCDATE') !== false || 
            stripos($sqlUpper, 'CREATEDATE') !== false ||
            stripos($sqlUpper, 'UPDATEDATE') !== false) {
            
            $hasDateFilter = preg_match('/WHERE\s+.*?(DOCDATE|CREATEDATE|UPDATEDATE|TAXDATE)/i', $sql);
            if (!$hasDateFilter) {
                error_log("💡 Sugestão: Query contém campos de data mas não tem filtro de data. Considere adicionar WHERE com filtro de data para melhor performance.");
            }
        }
        
        // 2. Apenas remover espaços em branco no início e fim (não normalizar espaços internos)
        // A normalização agressiva de espaços pode quebrar strings literais, comentários ou funções
        $sql = trim($sql);
        
        // 3. NÃO converter backticks automaticamente - pode quebrar queries válidas
        // Deixar o banco de dados lidar com os identificadores conforme sua sintaxe
        
        // 4. Log apenas se houver mudança (apenas trim não deve mudar nada significativo)
        if ($sql !== $originalSql && strlen(trim($originalSql)) !== strlen($sql)) {
            error_log("⚡ Query SQL otimizada (apenas trim)");
        }
        
        return $sql;
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

