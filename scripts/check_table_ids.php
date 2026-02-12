<?php
/**
 * Script CLI para revisar a configuração da coluna "id" em todas as tabelas do banco.
 *
 * Objetivo:
 *   - Identificar tabelas em que:
 *       * a coluna "id" não é PRIMARY KEY;
 *       * a coluna "id" não é AUTO_INCREMENT;
 *       * a coluna "id" permite NULL;
 *       * a coluna "id" não é UNSIGNED.
 *   - Ajudar a encontrar problemas semelhantes ao que ocorreu em adms_trainings.
 *
 * Uso (no servidor / ambiente local):
 *   php scripts/check_table_ids.php
 *
 * Saída:
 *   - Lista todas as tabelas que possuem coluna "id", indicando "OK" quando
 *     a configuração estiver adequada e, caso contrário, exibindo os problemas
 *     encontrados.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\adms\Models\Repository\TrainingsRepository;

echo "=== Verificação de configuração de IDs nas tabelas ===\n";
echo "Iniciado em: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Usamos qualquer Repository apenas para obter a conexão PDO
    $repo = new TrainingsRepository();
    /** @var PDO $pdo */
    $pdo = $repo->getConnection();

    $dbName = $_ENV['DB_NAME'] ?? null;
    if (!$dbName) {
        throw new RuntimeException('Variável de ambiente DB_NAME não encontrada.');
    }

    echo "Banco de dados: {$dbName}\n\n";

    // Buscar todas as tabelas que possuem coluna "id"
    $sql = "
        SELECT 
            c.TABLE_NAME,
            c.COLUMN_NAME,
            c.COLUMN_TYPE,
            c.IS_NULLABLE,
            c.COLUMN_KEY,
            c.EXTRA
        FROM INFORMATION_SCHEMA.COLUMNS c
        WHERE 
            c.TABLE_SCHEMA = :db
            AND c.COLUMN_NAME = 'id'
        ORDER BY c.TABLE_NAME
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':db', $dbName, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo "Nenhuma tabela com coluna 'id' encontrada.\n";
        exit(0);
    }

    $problemas = [];

    foreach ($rows as $row) {
        $table = $row['TABLE_NAME'];
        $colType = strtolower($row['COLUMN_TYPE'] ?? '');
        $isNullable = strtoupper($row['IS_NULLABLE'] ?? 'YES');
        $colKey = strtoupper($row['COLUMN_KEY'] ?? '');
        $extra = strtolower($row['EXTRA'] ?? '');

        $issues = [];

        if ($colKey !== 'PRI') {
            $issues[] = 'id não é PRIMARY KEY';
        }
        if (strpos($extra, 'auto_increment') === false) {
            $issues[] = 'id não é AUTO_INCREMENT';
        }
        if ($isNullable === 'YES') {
            $issues[] = 'id permite NULL';
        }
        if (!str_contains($colType, 'unsigned')) {
            $issues[] = 'id não é UNSIGNED';
        }

        if (empty($issues)) {
            echo "[OK]  {$table}.id  ({$row['COLUMN_TYPE']}, NOT NULL, PRIMARY KEY, AUTO_INCREMENT)\n";
        } else {
            echo "[!]  {$table}.id  ({$row['COLUMN_TYPE']})\n";
            foreach ($issues as $issue) {
                echo "      - {$issue}\n";
            }
            $problemas[] = [
                'table' => $table,
                'issues' => $issues,
            ];
        }
    }

    echo "\nResumo:\n";
    if (empty($problemas)) {
        echo "Todas as tabelas com coluna 'id' parecem configuradas corretamente.\n";
    } else {
        echo "Tabelas com possíveis problemas na coluna 'id': " . count($problemas) . "\n";
        foreach ($problemas as $p) {
            echo " - {$p['table']} (" . implode('; ', $p['issues']) . ")\n";
        }
    }

    echo "\nConcluído em: " . date('Y-m-d H:i:s') . "\n";
    exit(0);
} catch (\Throwable $e) {
    echo "ERRO ao verificar IDs das tabelas: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}


