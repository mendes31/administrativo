<?php
/**
 * Script CLI para regenerar IDs da tabela adms_trainings de forma sequencial,
 * preservando todos os dados e atualizando FKs relacionadas.
 *
 * Uso no servidor:
 *   php scripts/regenerate_training_ids.php
 *
 * IMPORTANTE:
 *   - Este script faz BACKUP dos dados antes de regenerar.
 *   - Atualiza automaticamente todas as FKs relacionadas.
 *   - Executa tudo em transação (ROLLBACK se der erro).
 */

require __DIR__ . '/../vendor/autoload.php';

use App\adms\Models\Services\DbConnection;

class TmpConnTraining extends DbConnection {}
$conn = (new TmpConnTraining())->getConnection(); /** @var PDO $conn */

echo "=== Regeneração de IDs da tabela adms_trainings ===\n";
echo "Iniciado em: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $conn->beginTransaction();

    // 1) Fazer backup dos dados atuais
    echo "1) Fazendo backup dos dados atuais...\n";
    $conn->exec('DROP TEMPORARY TABLE IF EXISTS adms_trainings_backup');
    $conn->exec('CREATE TEMPORARY TABLE adms_trainings_backup AS SELECT * FROM adms_trainings ORDER BY id ASC');
    $stmtCount = $conn->query('SELECT COUNT(*) FROM adms_trainings_backup');
    $totalRegistros = (int)$stmtCount->fetchColumn();
    echo "   -> {$totalRegistros} registros copiados para backup.\n\n";

    if ($totalRegistros === 0) {
        echo "Nenhum registro para regenerar. Abortando.\n";
        $conn->rollBack();
        exit(0);
    }

    // 2) Criar mapeamento de IDs antigos -> novos
    echo "2) Criando mapeamento de IDs (antigo -> novo)...\n";
    $conn->exec('DROP TEMPORARY TABLE IF EXISTS id_mapping');
    $conn->exec('
        CREATE TEMPORARY TABLE id_mapping (
            old_id INT UNSIGNED,
            new_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
        ) AS
        SELECT id AS old_id
        FROM adms_trainings
        ORDER BY id ASC
    ');
    $stmtMapping = $conn->query('SELECT COUNT(*) FROM id_mapping');
    $totalMapping = (int)$stmtMapping->fetchColumn();
    echo "   -> {$totalMapping} IDs mapeados.\n\n";

    // 3) Limpar tabela original
    echo "3) Limpando tabela original...\n";
    $conn->exec('TRUNCATE TABLE adms_trainings');
    echo "   -> Tabela limpa.\n\n";

    // 4) Reinserir dados com novos IDs sequenciais
    echo "4) Reinserindo dados com novos IDs sequenciais...\n";
    $sqlInsert = "
        INSERT INTO adms_trainings (
            nome, codigo, versao, prazo_treinamento, tipo, instrutor,
            carga_horaria, ativo, created_at, updated_at,
            instructor_user_id, instructor_email, instructor_name,
            reciclagem, reciclagem_periodo,
            area_responsavel_id, area_elaborador_id, tipo_obrigatoriedade
        )
        SELECT 
            b.nome, b.codigo, b.versao, b.prazo_treinamento, b.tipo, b.instrutor,
            b.carga_horaria, b.ativo, b.created_at, b.updated_at,
            b.instructor_user_id, b.instructor_email, b.instructor_name,
            b.reciclagem, b.reciclagem_periodo,
            b.area_responsavel_id, b.area_elaborador_id, b.tipo_obrigatoriedade
        FROM adms_trainings_backup b
        ORDER BY b.id ASC
    ";
    $conn->exec($sqlInsert);
    $stmtNew = $conn->query('SELECT COUNT(*) FROM adms_trainings');
    $totalNovos = (int)$stmtNew->fetchColumn();
    echo "   -> {$totalNovos} registros reinseridos.\n\n";

    // 5) Recriar mapeamento com os novos IDs gerados
    // Como os dados foram inseridos na mesma ordem, podemos mapear por posição
    echo "5) Recriando mapeamento com novos IDs...\n";
    $conn->exec('DROP TEMPORARY TABLE IF EXISTS id_mapping');
    $conn->exec('
        CREATE TEMPORARY TABLE id_mapping (
            old_id INT UNSIGNED,
            new_id INT UNSIGNED,
            INDEX idx_old (old_id),
            INDEX idx_new (new_id)
        )
    ');
    
    // Buscar dados do backup (ordenados por id ASC) e da tabela nova (ordenados por id ASC)
    // Como inserimos na mesma ordem, o primeiro registro do backup corresponde ao primeiro novo ID
    $stmtBackup = $conn->query('SELECT id AS old_id FROM adms_trainings_backup ORDER BY id ASC');
    $stmtNewIds = $conn->query('SELECT id AS new_id FROM adms_trainings ORDER BY id ASC');
    
    $oldIds = $stmtBackup->fetchAll(PDO::FETCH_COLUMN);
    $newIds = $stmtNewIds->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($oldIds) !== count($newIds)) {
        throw new RuntimeException('Erro: quantidade de IDs antigos e novos não confere.');
    }
    
    $stmtMap = $conn->prepare('INSERT INTO id_mapping (old_id, new_id) VALUES (?, ?)');
    for ($i = 0; $i < count($oldIds); $i++) {
        $stmtMap->execute([$oldIds[$i], $newIds[$i]]);
    }
    echo "   -> Mapeamento criado com " . count($oldIds) . " pares.\n\n";

    // 6) Atualizar FKs nas tabelas relacionadas
    echo "6) Atualizando FKs nas tabelas relacionadas...\n";
    
    $tabelasFK = [
        'adms_training_users' => 'adms_training_id',
        'adms_training_applications' => 'adms_training_id',
        'adms_training_positions' => 'adms_training_id',
        'adms_training_contents' => 'adms_training_id',
        'adms_training_evaluations' => 'adms_training_id',
    ];
    
    foreach ($tabelasFK as $tabela => $colunaFK) {
        $sqlUpdate = "
            UPDATE {$tabela} t
            INNER JOIN id_mapping m ON t.{$colunaFK} = m.old_id
            SET t.{$colunaFK} = m.new_id
        ";
        $stmt = $conn->prepare($sqlUpdate);
        $stmt->execute();
        $afetadas = $stmt->rowCount();
        echo "   -> {$tabela}: {$afetadas} registros atualizados.\n";
    }
    echo "\n";

    // 7) Commit
    $conn->commit();
    
    echo "✅ Regeneração concluída com sucesso!\n";
    echo "   Total de registros regenerados: {$totalRegistros}\n";
    echo "   Concluído em: " . date('Y-m-d H:i:s') . "\n";
    exit(0);

} catch (\Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "❌ ERRO ao regenerar IDs: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

