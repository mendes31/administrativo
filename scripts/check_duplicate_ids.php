<?php
/**
 * Script CLI para identificar IDs duplicados em todas as tabelas
 * que possuem uma coluna chamada "id".
 *
 * Uso no servidor:
 *   php scripts/check_duplicate_ids.php
 *
 * Saída:
 *   - Lista apenas as tabelas onde existem valores de id repetidos,
 *     mostrando o valor do id e a quantidade de ocorrências.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\adms\Models\Services\DbConnection;

class TmpConnection extends DbConnection {}

/** @var PDO $conn */
$conn = (new TmpConnection())->getConnection();

$dbName = $_ENV['DB_NAME'] ?? null;
if (!$dbName) {
    echo "Erro: DB_NAME não definido nas variáveis de ambiente.\n";
    exit(1);
}

echo "=== Verificando IDs duplicados em tabelas com coluna 'id' ===\n";
echo "Banco: {$dbName}\n\n";

// Buscar todas as tabelas que possuem coluna "id"
$sql = "
    SELECT TABLE_NAME
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = :db
      AND COLUMN_NAME = 'id'
    ORDER BY TABLE_NAME
";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':db', $dbName, PDO::PARAM_STR);
$stmt->execute();
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($tables)) {
    echo "Nenhuma tabela com coluna 'id' encontrada.\n";
    exit(0);
}

$foundAny = false;

foreach ($tables as $table) {
    $query = $conn->query("SELECT id, COUNT(*) AS qtd FROM `{$table}` GROUP BY id HAVING COUNT(*) > 1");
    $dups = $query->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($dups)) {
        $foundAny = true;
        echo "Tabela: {$table}\n";
        foreach ($dups as $row) {
            $id  = $row['id'];
            $qtd = (int) $row['qtd'];
            echo "  id = {$id} repetido {$qtd} vezes\n";
        }
        echo "\n";
    }
}

if (!$foundAny) {
    echo "Nenhuma duplicidade encontrada em colunas 'id'.\n";
}


