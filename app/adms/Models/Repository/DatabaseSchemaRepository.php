<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Metadados do schema MySQL do sistema (INFORMATION_SCHEMA).
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

    public function getDatabaseName(): string
    {
        return (string) ($_ENV['DB_NAME'] ?? '');
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
     * @return list<array<string, mixed>>
     */
    public function listTables(?string $moduleFilter = null, string $search = ''): array
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

        $search = strtolower(trim($search));
        $moduleFilter = $moduleFilter !== null && $moduleFilter !== '' ? $moduleFilter : null;

        $out = [];
        foreach ($rows as $row) {
            $tableName = (string) ($row['table_name'] ?? '');
            $module = $this->resolveModule($tableName);
            if ($moduleFilter !== null && $module !== $moduleFilter) {
                continue;
            }
            if ($search !== '') {
                $blob = strtolower($tableName . ' ' . ($row['table_comment'] ?? '') . ' ' . $module);
                if (!str_contains($blob, $search)) {
                    continue;
                }
            }
            $row['module'] = $module;
            $out[] = $row;
        }

        return $out;
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

    /**
     * @return list<string>
     */
    public function listModules(): array
    {
        return $this->extractModules($this->listTables());
    }

    public function tableExists(string $tableName): bool
    {
        if (!$this->isValidTableIdentifier($tableName)) {
            return false;
        }

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
    public function getTableSummary(string $tableName): ?array
    {
        if (!$this->tableExists($tableName)) {
            return null;
        }

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
    public function getTableColumns(string $tableName): array
    {
        if (!$this->tableExists($tableName)) {
            return [];
        }

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
    public function getTableIndexes(string $tableName): array
    {
        if (!$this->tableExists($tableName)) {
            return [];
        }

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
    public function getTableForeignKeys(string $tableName): array
    {
        if (!$this->tableExists($tableName)) {
            return [];
        }

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

    /**
     * @return list<array<string, mixed>>
     */
    public function getTableColumnsDetailed(string $tableName): array
    {
        $fks = $this->getTableForeignKeys($tableName);
        $fkByColumn = [];
        foreach ($fks as $fk) {
            $fkByColumn[(string) ($fk['COLUMN_NAME'] ?? '')] = $fk;
        }

        $out = [];
        foreach ($this->getTableColumns($tableName) as $col) {
            $name = (string) ($col['COLUMN_NAME'] ?? '');
            $parsed = self::parseMysqlColumnType((string) ($col['COLUMN_TYPE'] ?? ''));
            $fk = $fkByColumn[$name] ?? null;
            $out[] = array_merge($col, $parsed, [
                'relation_table' => $fk['REFERENCED_TABLE_NAME'] ?? null,
                'relation_column' => $fk['REFERENCED_COLUMN_NAME'] ?? null,
                'constraints_label' => self::formatColumnConstraints($col),
            ]);
        }

        return $out;
    }

    public function getCreateTableDdl(string $tableName): ?string
    {
        if (!$this->tableExists($tableName)) {
            return null;
        }

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
