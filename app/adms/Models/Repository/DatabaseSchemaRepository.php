<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DatabaseSchemaCacheService;
use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Metadados do schema MySQL do sistema (INFORMATION_SCHEMA) com cache em disco.
 */
class DatabaseSchemaRepository extends DbConnection
{
    /** @var array<string, string> prefixo da tabela → módulo lógico */
    private const MODULE_PREFIXES = [
        'adms_' => 'Administração / Sistema',
        'inv_' => 'Estoque',
        'crm_' => 'CRM',
        'sst_' => 'Segurança e Medicina (SST)',
        'lgpd_' => 'LGPD',
        'proj_' => 'Gestão de Projetos',
        'rh_' => 'RH / Recrutamento',
        'room_' => 'Reserva de Salas',
        'rooms_' => 'Reserva de Salas',
        'booking_' => 'Reserva de Salas',
        'sac_' => 'SAC',
        'pe_' => 'Planejamento Estratégico',
        'phinx' => 'Migrações (Phinx)',
    ];

    private ?DatabaseSchemaCacheService $cache = null;

    public function getDatabaseName(): string
    {
        return (string) ($_ENV['DB_NAME'] ?? '');
    }

    public function getCacheService(): DatabaseSchemaCacheService
    {
        if ($this->cache === null) {
            $this->cache = new DatabaseSchemaCacheService($this->getDatabaseName());
        }

        return $this->cache;
    }

    public function getCatalogUpdatedAt(): ?string
    {
        $catalog = $this->getCacheService()->getCatalog();

        return is_array($catalog) ? (string) ($catalog['updated_at'] ?? '') : null;
    }

    public function hasCatalogCache(): bool
    {
        return $this->getCacheService()->getCatalog() !== null;
    }

    /**
     * Garante catálogo em cache (gera automaticamente na primeira visita).
     */
    public function ensureCatalogCache(): void
    {
        if (!$this->hasCatalogCache()) {
            $this->refreshCatalogCache();
        }
    }

    /**
     * Detalhe desatualizado após novo catálogo ou mudança no número de colunas.
     */
    public function isTableDetailStale(string $tableName): bool
    {
        if (!$this->hasTableDetailCache($tableName)) {
            return true;
        }

        $catalogAt = $this->getCatalogUpdatedAt();
        $detailAt = $this->getTableDetailUpdatedAt($tableName);
        if ($catalogAt !== null && $catalogAt !== '' && $detailAt !== null && $detailAt !== '') {
            if (strtotime($catalogAt) > strtotime($detailAt)) {
                return true;
            }
        }

        $catalogRow = $this->findCatalogTableRow($tableName);
        if ($catalogRow === null) {
            return true;
        }

        $cached = $this->getCacheService()->getTableDetail($tableName);
        $cachedColumnCount = is_array($cached['columns'] ?? null) ? count($cached['columns']) : 0;
        $catalogColumnCount = (int) ($catalogRow['column_count'] ?? 0);

        return $catalogColumnCount !== $cachedColumnCount;
    }

    /**
     * Carrega colunas/índices da tabela no cache se ainda não existirem ou estiverem desatualizados.
     */
    public function ensureTableDetailCache(string $tableName): void
    {
        if ($this->isTableDetailStale($tableName)) {
            $this->refreshTableDetailCache($tableName);
        }
    }

    /**
     * Reconstrói o catálogo e invalida detalhes das tabelas (novas tabelas/colunas após migrations).
     *
     * @return array{updated_at: string, table_count: int}
     */
    public function refreshCatalogCache(): array
    {
        $cache = $this->getCacheService();
        $cache->clearAll();

        $tables = $this->fetchAllTablesFromDatabase();
        $modules = $this->extractModules($tables);
        $cache->putCatalog($tables, $modules);

        $catalog = $cache->getCatalog();

        return [
            'updated_at' => (string) ($catalog['updated_at'] ?? date('Y-m-d H:i:s')),
            'table_count' => count($tables),
        ];
    }

    /**
     * Atualiza o cache de detalhe de uma única tabela.
     *
     * @return array{updated_at: string, table: string}
     */
    public function refreshTableDetailCache(string $tableName): array
    {
        if (!$this->isValidTableIdentifier($tableName) || !$this->tableExistsInDatabase($tableName)) {
            throw new \InvalidArgumentException('Tabela inválida ou inexistente.');
        }

        $detail = $this->fetchTableDetailFromDatabase($tableName);
        $this->getCacheService()->putTableDetail($tableName, $detail);

        return [
            'updated_at' => (string) ($detail['updated_at'] ?? date('Y-m-d H:i:s')),
            'table' => $tableName,
        ];
    }

    public function resolveModule(string $tableName): string
    {
        $tableName = strtolower($tableName);
        foreach (self::MODULE_PREFIXES as $prefix => $label) {
            if (str_starts_with($tableName, $prefix)) {
                return $label;
            }
        }

        return 'Geral';
    }

    /**
     * Lista tabelas a partir do cache (sem consultar INFORMATION_SCHEMA).
     *
     * @return list<array<string, mixed>>
     */
    public function listTables(?string $moduleFilter = null, string $search = ''): array
    {
        $catalog = $this->getCacheService()->getCatalog();
        if ($catalog === null) {
            return [];
        }

        $search = strtolower(trim($search));
        $moduleFilter = $moduleFilter !== null && $moduleFilter !== '' ? $moduleFilter : null;

        $out = [];
        foreach ($catalog['tables'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $module = (string) ($row['module'] ?? 'Geral');
            if ($moduleFilter !== null && $module !== $moduleFilter) {
                continue;
            }
            if ($search !== '') {
                $tableName = (string) ($row['table_name'] ?? '');
                $blob = strtolower($tableName . ' ' . ($row['table_comment'] ?? '') . ' ' . $module);
                if (!str_contains($blob, $search)) {
                    continue;
                }
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public function listModules(): array
    {
        $catalog = $this->getCacheService()->getCatalog();
        if ($catalog !== null && is_array($catalog['modules'] ?? null)) {
            return $catalog['modules'];
        }

        return [];
    }

    /**
     * @param list<array<string, mixed>> $tables
     * @return list<string>
     */
    public function extractModules(array $tables): array
    {
        $modules = [];
        foreach ($tables as $row) {
            $modules[(string) ($row['module'] ?? 'Geral')] = true;
        }
        $keys = array_keys($modules);
        sort($keys, SORT_NATURAL | SORT_FLAG_CASE);

        return $keys;
    }

    public function tableExists(string $tableName): bool
    {
        if (!$this->isValidTableIdentifier($tableName)) {
            return false;
        }

        $catalog = $this->getCacheService()->getCatalog();
        if ($catalog !== null) {
            foreach ($catalog['tables'] as $row) {
                if (is_array($row) && (string) ($row['table_name'] ?? '') === $tableName) {
                    return true;
                }
            }

            return false;
        }

        return $this->tableExistsInDatabase($tableName);
    }

    public function tableExistsInLiveDatabase(string $tableName): bool
    {
        if (!$this->isValidTableIdentifier($tableName)) {
            return false;
        }

        return $this->tableExistsInDatabase($tableName);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findCatalogTableRow(string $tableName): ?array
    {
        foreach ($this->listTables() as $row) {
            if ((string) ($row['table_name'] ?? '') === $tableName) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTableSummary(string $tableName): ?array
    {
        $detail = $this->getTableDetailBundle($tableName);

        return is_array($detail['summary'] ?? null) ? $detail['summary'] : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getTableColumnsDetailed(string $tableName): array
    {
        $detail = $this->getTableDetailBundle($tableName);
        $columns = $detail['columns'] ?? [];

        return is_array($columns) ? $columns : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getTableIndexes(string $tableName): array
    {
        $detail = $this->getTableDetailBundle($tableName);
        $indexes = $detail['indexes'] ?? [];

        return is_array($indexes) ? $indexes : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getTableForeignKeys(string $tableName): array
    {
        $detail = $this->getTableDetailBundle($tableName);
        $fks = $detail['foreign_keys'] ?? [];

        return is_array($fks) ? $fks : [];
    }

    public function getCreateTableDdl(string $tableName): ?string
    {
        $detail = $this->getTableDetailBundle($tableName);
        $ddl = $detail['create_ddl'] ?? null;

        return is_string($ddl) && $ddl !== '' ? $ddl : null;
    }

    public function getTableDetailUpdatedAt(string $tableName): ?string
    {
        $cached = $this->getCacheService()->getTableDetail($tableName);

        return is_array($cached) ? (string) ($cached['updated_at'] ?? '') : null;
    }

    public function hasTableDetailCache(string $tableName): bool
    {
        return $this->getCacheService()->getTableDetail($tableName) !== null;
    }

    /**
     * @return array<string, mixed>
     */
    private function getTableDetailBundle(string $tableName): array
    {
        if (!$this->isValidTableIdentifier($tableName)) {
            return [];
        }

        $cached = $this->getCacheService()->getTableDetail($tableName);

        return is_array($cached) ? $cached : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAllTablesFromDatabase(): array
    {
        $db = $this->getDatabaseName();
        if ($db === '') {
            return [];
        }

        $sql = "SELECT
                    t.TABLE_NAME AS table_name,
                    t.TABLE_COMMENT AS table_comment,
                    t.ENGINE AS engine,
                    t.TABLE_ROWS AS table_rows,
                    COALESCE(cc.column_count, 0) AS column_count,
                    COALESCE(ic.index_count, 0) AS index_count
                FROM INFORMATION_SCHEMA.TABLES t
                LEFT JOIN (
                    SELECT TABLE_NAME, COUNT(*) AS column_count
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = :schema_cols
                    GROUP BY TABLE_NAME
                ) cc ON cc.TABLE_NAME = t.TABLE_NAME
                LEFT JOIN (
                    SELECT TABLE_NAME, COUNT(DISTINCT INDEX_NAME) AS index_count
                    FROM INFORMATION_SCHEMA.STATISTICS
                    WHERE TABLE_SCHEMA = :schema_stats
                    GROUP BY TABLE_NAME
                ) ic ON ic.TABLE_NAME = t.TABLE_NAME
                WHERE t.TABLE_SCHEMA = :schema
                  AND t.TABLE_TYPE = 'BASE TABLE'
                ORDER BY t.TABLE_NAME ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':schema', $db, PDO::PARAM_STR);
        $stmt->bindValue(':schema_cols', $db, PDO::PARAM_STR);
        $stmt->bindValue(':schema_stats', $db, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $row) {
            $tableName = (string) ($row['table_name'] ?? '');
            $row['module'] = $this->resolveModule($tableName);
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchTableDetailFromDatabase(string $tableName): array
    {
        $summary = $this->fetchTableSummaryFromDatabase($tableName);
        if ($summary === null) {
            throw new \InvalidArgumentException('Tabela não encontrada.');
        }

        $fks = $this->fetchTableForeignKeysFromDatabase($tableName);
        $fkByColumn = [];
        foreach ($fks as $fk) {
            $fkByColumn[(string) ($fk['COLUMN_NAME'] ?? '')] = $fk;
        }

        $columns = [];
        foreach ($this->fetchTableColumnsFromDatabase($tableName) as $col) {
            $name = (string) ($col['COLUMN_NAME'] ?? '');
            $parsed = self::parseMysqlColumnType((string) ($col['COLUMN_TYPE'] ?? ''));
            $fk = $fkByColumn[$name] ?? null;
            $columns[] = array_merge($col, $parsed, [
                'relation_table' => $fk['REFERENCED_TABLE_NAME'] ?? null,
                'relation_column' => $fk['REFERENCED_COLUMN_NAME'] ?? null,
                'constraints_label' => self::formatColumnConstraints($col),
            ]);
        }

        return [
            'summary' => $summary,
            'columns' => $columns,
            'indexes' => $this->fetchTableIndexesFromDatabase($tableName),
            'foreign_keys' => $fks,
            'create_ddl' => $this->fetchCreateTableDdlFromDatabase($tableName),
        ];
    }

    private function tableExistsInDatabase(string $tableName): bool
    {
        $sql = 'SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND TABLE_TYPE = \'BASE TABLE\'
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':schema', $this->getDatabaseName(), PDO::PARAM_STR);
        $stmt->bindValue(':table', $tableName, PDO::PARAM_STR);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchTableSummaryFromDatabase(string $tableName): ?array
    {
        $sql = "SELECT TABLE_NAME AS table_name, TABLE_COMMENT AS table_comment,
                       ENGINE AS engine, TABLE_ROWS AS table_rows,
                       CREATE_TIME AS create_time, UPDATE_TIME AS update_time
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table
                LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':schema', $this->getDatabaseName(), PDO::PARAM_STR);
        $stmt->bindValue(':table', $tableName, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['module'] = $this->resolveModule($tableName);

        return $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchTableColumnsFromDatabase(string $tableName): array
    {
        $sql = 'SELECT ORDINAL_POSITION, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY,
                       COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table
                ORDER BY ORDINAL_POSITION ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':schema', $this->getDatabaseName(), PDO::PARAM_STR);
        $stmt->bindValue(':table', $tableName, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchTableIndexesFromDatabase(string $tableName): array
    {
        $sql = 'SELECT INDEX_NAME, NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR \', \') AS columns
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table
                GROUP BY INDEX_NAME, NON_UNIQUE
                ORDER BY INDEX_NAME ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':schema', $this->getDatabaseName(), PDO::PARAM_STR);
        $stmt->bindValue(':table', $tableName, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchTableForeignKeysFromDatabase(string $tableName): array
    {
        $sql = 'SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':schema', $this->getDatabaseName(), PDO::PARAM_STR);
        $stmt->bindValue(':table', $tableName, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchCreateTableDdlFromDatabase(string $tableName): ?string
    {
        $safe = str_replace('`', '``', $tableName);
        $stmt = $this->getConnection()->query('SHOW CREATE TABLE `' . $safe . '`');
        if ($stmt === false) {
            return null;
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? (string) ($row['Create Table'] ?? '') : null;
    }

    /**
     * @return array{base_type: string, length: string, decimals: string}
     */
    public static function parseMysqlColumnType(string $columnType): array
    {
        $columnType = strtolower(trim($columnType));
        if (preg_match('/^([a-z]+)(?:\((\d+)(?:,(\d+))?\))?/i', $columnType, $m)) {
            return [
                'base_type' => $m[1],
                'length' => $m[2] ?? '',
                'decimals' => $m[3] ?? '',
            ];
        }

        return ['base_type' => $columnType, 'length' => '', 'decimals' => ''];
    }

    /**
     * @param array<string, mixed> $col
     */
    public static function formatColumnConstraints(array $col): string
    {
        $parts = [];
        $key = (string) ($col['COLUMN_KEY'] ?? '');
        if ($key === 'PRI') {
            $parts[] = 'PRIMARY KEY';
        } elseif ($key === 'UNI') {
            $parts[] = 'UNIQUE';
        } elseif ($key === 'MUL') {
            $parts[] = 'INDEX';
        }
        if (str_contains((string) ($col['EXTRA'] ?? ''), 'auto_increment')) {
            $parts[] = 'AUTO_INCREMENT';
        }
        if (($col['IS_NULLABLE'] ?? '') === 'NO') {
            $parts[] = 'NOT NULL';
        }

        return $parts !== [] ? implode(', ', $parts) : '—';
    }

    public function tablePathSegment(string $tableName): string
    {
        return rawurlencode(str_replace('_', '-', $tableName));
    }

    public function isValidTableIdentifier(string $tableName): bool
    {
        return $tableName !== '' && (bool) preg_match('/^[A-Za-z0-9_]+$/', $tableName);
    }
}
