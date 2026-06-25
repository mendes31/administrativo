<?php
/**
 * Gera e persiste comentários (TABLE_COMMENT / COLUMN_COMMENT) no MySQL.
 *
 * Uso:
 *   php scripts/generate_database_schema_comments.php              # aplica em tabelas/colunas sem comentário
 *   php scripts/generate_database_schema_comments.php --dry-run      # apenas exibe o que seria aplicado
 *   php scripts/generate_database_schema_comments.php --force        # sobrescreve comentários existentes
 *   php scripts/generate_database_schema_comments.php --tables-only  # só comentários de tabela
 *   php scripts/generate_database_schema_comments.php --columns-only # só comentários de coluna
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\adms\Models\Repository\DatabaseSchemaRepository;
use App\adms\Models\Services\DatabaseSchemaDescriptionService;

Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..')->load();

$dryRun = in_array('--dry-run', $argv, true);
$force = in_array('--force', $argv, true);
$tablesOnly = in_array('--tables-only', $argv, true);
$columnsOnly = in_array('--columns-only', $argv, true);
$doTables = !$columnsOnly;
$doColumns = !$tablesOnly;

$repo = new DatabaseSchemaRepository();
$descriptions = $repo->getDescriptionService();
$pdo = $repo->getConnection();
$db = $repo->getDatabaseName();

echo "Base: {$db}\n";
echo 'Modo: ' . ($dryRun ? 'dry-run' : 'aplicar') . ($force ? ' (force)' : '') . "\n\n";

$sqlTables = "SELECT TABLE_NAME, TABLE_COMMENT
              FROM INFORMATION_SCHEMA.TABLES
              WHERE TABLE_SCHEMA = :schema AND TABLE_TYPE = 'BASE TABLE'
              ORDER BY TABLE_NAME";
$stmtTables = $pdo->prepare($sqlTables);
$stmtTables->bindValue(':schema', $db);
$stmtTables->execute();
$tables = $stmtTables->fetchAll(PDO::FETCH_ASSOC) ?: [];

$tableUpdates = 0;
$columnUpdates = 0;
$errors = 0;

foreach ($tables as $tableRow) {
    $tableName = (string) $tableRow['TABLE_NAME'];
    $existingTableComment = trim((string) ($tableRow['TABLE_COMMENT'] ?? ''));

    $sqlCols = 'SELECT ORDINAL_POSITION, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT,
                       EXTRA, COLUMN_COMMENT, COLUMN_KEY
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table
                ORDER BY ORDINAL_POSITION ASC';
    $stmtCols = $pdo->prepare($sqlCols);
    $stmtCols->bindValue(':schema', $db);
    $stmtCols->bindValue(':table', $tableName);
    $stmtCols->execute();
    $rawColumns = $stmtCols->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $sqlFk = 'SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
              FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table
                AND REFERENCED_TABLE_NAME IS NOT NULL';
    $stmtFk = $pdo->prepare($sqlFk);
    $stmtFk->bindValue(':schema', $db);
    $stmtFk->bindValue(':table', $tableName);
    $stmtFk->execute();
    $fks = $stmtFk->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $fkByColumn = [];
    foreach ($fks as $fk) {
        $fkByColumn[(string) $fk['COLUMN_NAME']] = $fk;
    }

    $enrichedColumns = [];
    foreach ($rawColumns as $col) {
        $name = (string) $col['COLUMN_NAME'];
        $fk = $fkByColumn[$name] ?? null;
        $parsed = DatabaseSchemaRepository::parseMysqlColumnType((string) ($col['COLUMN_TYPE'] ?? ''));
        $enrichedColumns[] = array_merge($col, $parsed, [
            'relation_table' => $fk['REFERENCED_TABLE_NAME'] ?? null,
            'relation_column' => $fk['REFERENCED_COLUMN_NAME'] ?? null,
        ]);
    }

    if ($doTables && ($force || $existingTableComment === '')) {
        $newTableComment = $descriptions->describeTable($tableName, $enrichedColumns, $fks);
        echo "[TABELA] {$tableName}\n  → {$newTableComment}\n";
        if (!$dryRun) {
            try {
                $safeTable = str_replace('`', '``', $tableName);
                $quoted = $pdo->quote($newTableComment);
                $pdo->exec("ALTER TABLE `{$safeTable}` COMMENT = {$quoted}");
                $tableUpdates++;
            } catch (Throwable $e) {
                echo "  ERRO: {$e->getMessage()}\n";
                $errors++;
            }
        } else {
            $tableUpdates++;
        }
    }

    if (!$doColumns) {
        continue;
    }

    foreach ($enrichedColumns as $col) {
        $colName = (string) ($col['COLUMN_NAME'] ?? '');
        $existingColComment = trim((string) ($col['COLUMN_COMMENT'] ?? ''));
        if (!$force && $existingColComment !== '') {
            continue;
        }

        $newColComment = $descriptions->describeColumn(
            $colName,
            $col,
            isset($col['relation_table']) && $col['relation_table'] !== null
                ? (string) $col['relation_table']
                : null
        );

        $columnType = (string) ($col['COLUMN_TYPE'] ?? '');
        $nullable = (($col['IS_NULLABLE'] ?? '') === 'YES') ? 'NULL' : 'NOT NULL';
        $default = $col['COLUMN_DEFAULT'] ?? null;
        $extra = trim((string) ($col['EXTRA'] ?? ''));
        $extra = trim(preg_replace('/\bDEFAULT_GENERATED\b/i', '', $extra) ?? $extra);

        $defSql = '';
        $extraHandlesDefault = $extra !== '' && str_contains($extra, 'on update CURRENT_TIMESTAMP');
        if (!$extraHandlesDefault && $default !== null) {
            if (strtoupper((string) $default) === 'CURRENT_TIMESTAMP') {
                $defSql = ' DEFAULT CURRENT_TIMESTAMP';
            } else {
                $defSql = ' DEFAULT ' . (is_numeric($default) ? $default : $pdo->quote((string) $default));
            }
        } elseif (!$extraHandlesDefault && ($col['IS_NULLABLE'] ?? '') === 'YES' && $default === null) {
            $defSql = ' DEFAULT NULL';
        }

        $extraSql = $extra !== '' ? ' ' . $extra : '';
        $safeTable = str_replace('`', '``', $tableName);
        $safeCol = str_replace('`', '``', $colName);
        $quotedComment = $pdo->quote($newColComment);

        $alter = "ALTER TABLE `{$safeTable}` MODIFY COLUMN `{$safeCol}` {$columnType} {$nullable}{$defSql}{$extraSql} COMMENT {$quotedComment}";

        if ($dryRun && $columnUpdates < 5) {
            echo "[COLUNA] {$tableName}.{$colName}\n  → {$newColComment}\n";
        }

        if (!$dryRun) {
            try {
                $pdo->exec($alter);
                $columnUpdates++;
            } catch (Throwable $e) {
                echo "ERRO coluna {$tableName}.{$colName}: {$e->getMessage()}\n";
                $errors++;
            }
        } else {
            $columnUpdates++;
        }
    }
}

if ($dryRun && $columnUpdates > 5) {
    echo "... e mais " . ($columnUpdates - 5) . " coluna(s)\n";
}

echo "\n";
echo "Tabelas atualizadas: {$tableUpdates}\n";
echo "Colunas atualizadas: {$columnUpdates}\n";
if ($errors > 0) {
    echo "Erros: {$errors}\n";
}

if (!$dryRun && ($tableUpdates > 0 || $columnUpdates > 0)) {
    echo "\nAtualizando cache do catálogo...\n";
    $repo->refreshCatalogCache();
    echo "Cache atualizado.\n";
}
