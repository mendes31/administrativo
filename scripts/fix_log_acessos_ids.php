<?php
/**
 * Script CLI para corrigir os IDs da tabela adms_log_acessos.
 *
 * Uso (no servidor):
 *   php scripts/fix_log_acessos_ids.php
 *
 * O que ele faz:
 *   - Cria uma cópia temporária da tabela com ID AUTO_INCREMENT e PRIMARY KEY.
 *   - Reinsere todos os registros na nova tabela, gerando novos IDs sequenciais,
 *     ordenados por data de acesso (mais antigos primeiro).
 *   - Renomeia as tabelas: a antiga vira _old (backup), a nova assume o nome original.
 *
 * IMPORTANTE:
 *   - Este script assume que NÃO existem chaves estrangeiras apontando para
 *     adms_log_acessos. Em geral, logs não são referenciados por outras tabelas.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\adms\Models\Repository\LogAcessosRepository;

echo "=== Correção de IDs em adms_log_acessos ===\n";
echo "Iniciando em: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $repo = new LogAcessosRepository();
    /** @var PDO $conn */
    $conn = $repo->getConnection();
    $conn->beginTransaction();

    echo "1) Criando tabela temporária adms_log_acessos_tmp...\n";

    // Criar tabela temporária com mesma estrutura
    $conn->exec('DROP TABLE IF EXISTS adms_log_acessos_tmp');
    $conn->exec('CREATE TABLE adms_log_acessos_tmp LIKE adms_log_acessos');

    // Ajustar estrutura da tabela temporária:
    // - id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
    echo "2) Ajustando estrutura da tabela temporária (AUTO_INCREMENT em id)...\n";
    // Em muitas instalações essa tabela de origem pode não ter PRIMARY KEY;
    // por isso ajustamos a coluna id diretamente, já definindo PRIMARY KEY.
    $conn->exec("
        ALTER TABLE adms_log_acessos_tmp
        MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
    ");

    echo "3) Copiando registros para a tabela temporária, gerando novos IDs sequenciais...\n";

    // Descobrir colunas (exceto id) para montar o INSERT corretamente
    $stmtCols = $conn->query("SHOW COLUMNS FROM adms_log_acessos");
    $cols = $stmtCols->fetchAll(PDO::FETCH_ASSOC);

    $nonIdCols = [];
    foreach ($cols as $col) {
        if (strtolower($col['Field']) !== 'id') {
            $nonIdCols[] = '`' . $col['Field'] . '`';
        }
    }
    if (empty($nonIdCols)) {
        throw new RuntimeException('Não foi possível identificar as colunas (exceto id) em adms_log_acessos.');
    }

    $colsList = implode(', ', $nonIdCols);

    // Inserir todos os registros, ordenando por data_acesso e id para manter ordem cronológica
    $sqlInsert = "
        INSERT INTO adms_log_acessos_tmp ($colsList)
        SELECT $colsList
        FROM adms_log_acessos
        ORDER BY data_acesso ASC, id ASC
    ";
    $rows = $conn->exec($sqlInsert);

    echo "   -> Registros copiados: {$rows}\n";

    echo "4) Renomeando tabelas (backup da antiga)...\n";

    // Renomear: original -> _old, tmp -> original
    $conn->exec('RENAME TABLE adms_log_acessos TO adms_log_acessos_old');
    $conn->exec('RENAME TABLE adms_log_acessos_tmp TO adms_log_acessos');

    $conn->commit();

    echo "\nCorreção concluída com sucesso.\n";
    echo "A tabela antiga foi renomeada para adms_log_acessos_old (backup).\n";
    echo "Concluído em: " . date('Y-m-d H:i:s') . "\n";
    exit(0);
} catch (\Throwable $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "ERRO ao corrigir IDs de adms_log_acessos: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}


