<?php
/**
 * Script CLI para regenerar IDs iguais a 0 em tabelas específicas,
 * atribuindo novos IDs sequenciais (maiores que o MAX(id) atual).
 *
 * Objetivo:
 *   - Corrigir registros com id = 0 sem perder dados,
 *     preparando o banco para receber PRIMARY KEY / AUTO_INCREMENT.
 *
 * Uso no servidor:
 *   php scripts/regenerate_ids_zero.php
 *
 * IMPORTANTE:
 *   - Este script foi pensado para as tabelas onde já detectamos
 *     id = 0 repetido. Ele cuida também das principais relações
 *     (FKs) de adms_trainings e lgpd_consentimentos.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\adms\Models\Services\DbConnection;

class TmpConnection2 extends DbConnection {}

/** @var PDO $conn */
$conn = (new TmpConnection2())->getConnection();

$dbName = $_ENV['DB_NAME'] ?? null;
if (!$dbName) {
    echo "Erro: DB_NAME não definido nas variáveis de ambiente.\n";
    exit(1);
}

echo "=== Regeneração de IDs iguais a 0 ===\n";
echo "Banco: {$dbName}\n\n";

/**
 * Tabelas simples: apenas corrigir id = 0, sem FKs conhecidas.
 */
$tabelasSimples = [
    'adms_login_attempts',
    'adms_logs',
    'adms_log_alteracoes_detalhes',
    'adms_pages',
    'adms_performance_reviews',
    'adms_sessions',
];

foreach ($tabelasSimples as $table) {
    try {
        $conn->beginTransaction();

        $stmtCount = $conn->query("SELECT COUNT(*) AS qtd FROM `{$table}` WHERE id = 0");
        $rowCount = $stmtCount->fetch(PDO::FETCH_ASSOC);
        $qtd = isset($rowCount['qtd']) ? (int)$rowCount['qtd'] : 0;

        if ($qtd === 0) {
            $conn->commit();
            echo "Tabela {$table}: nenhum registro com id = 0 encontrado. Nada a fazer.\n";
            continue;
        }

        $stmtMax = $conn->query("SELECT IFNULL(MAX(id), 0) AS max_id FROM `{$table}` WHERE id <> 0");
        $rowMax = $stmtMax->fetch(PDO::FETCH_ASSOC);
        $maxId = isset($rowMax['max_id']) ? (int)$rowMax['max_id'] : 0;

        echo "Tabela {$table}: {$qtd} registros com id = 0. max_id atual = {$maxId}. Regenerando...\n";

        // Atribuir novos IDs sequenciais para todos com id = 0
        // Obs.: usamos variável de usuário do MySQL para ir incrementando.
        $sqlSet = "SET @max_id := {$maxId}";
        $conn->exec($sqlSet);

        $sqlUpdate = "
            UPDATE `{$table}`
            SET id = (@max_id := @max_id + 1)
            WHERE id = 0
        ";
        $afetados = $conn->exec($sqlUpdate);

        $conn->commit();

        echo "  -> IDs atualizados: {$afetados}\n\n";
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo "  [ERRO] Falha ao atualizar tabela {$table}: " . $e->getMessage() . "\n";
    }
}

/**
 * Tabela adms_trainings: além de corrigir id = 0,
 * atualiza FKs conhecidas em tabelas de relacionamentos.
 */
try {
    $table = 'adms_trainings';
    $conn->beginTransaction();

    $stmtZeros = $conn->query("SELECT id FROM `{$table}` WHERE id = 0");
    $rowsZeros = $stmtZeros->fetchAll(PDO::FETCH_ASSOC);
    $qtd = count($rowsZeros);

    if ($qtd > 0) {
        $stmtMax = $conn->query("SELECT IFNULL(MAX(id), 0) AS max_id FROM `{$table}` WHERE id <> 0");
        $rowMax = $stmtMax->fetch(PDO::FETCH_ASSOC);
        $maxId = isset($rowMax['max_id']) ? (int)$rowMax['max_id'] : 0;

        echo "Tabela {$table}: {$qtd} registros com id = 0. max_id atual = {$maxId}. Regenerando com ajuste de FKs...\n";

        // Como todos os IDs problemáticos são 0, podemos tratar em bloco.
        $sqlSet = "SET @max_id := {$maxId}";
        $conn->exec($sqlSet);

        // Atualiza os próprios treinamentos
        $sqlUpdateTrainings = "
            UPDATE `{$table}`
            SET id = (@max_id := @max_id + 1)
            WHERE id = 0
        ";
        $afetadosTrainings = $conn->exec($sqlUpdateTrainings);

        // Agora precisamos atualizar FKs em tabelas relacionadas que tenham adms_training_id = 0.
        $fkTables = [
            ['nome' => 'adms_training_users',       'campo' => 'adms_training_id'],
            ['nome' => 'adms_training_applications','campo' => 'adms_training_id'],
            ['nome' => 'adms_training_positions',   'campo' => 'adms_training_id'],
            ['nome' => 'adms_training_contents',    'campo' => 'adms_training_id'],
            ['nome' => 'adms_training_evaluations', 'campo' => 'adms_training_id'],
        ];

        // Para simplificar e manter consistência, qualquer FK = 0 nessas tabelas
        // será atualizada para o menor novo ID gerado (primeiro id > maxId antigo).
        $stmtMinNovo = $conn->query("SELECT MIN(id) AS min_novo FROM `{$table}` WHERE id > {$maxId}");
        $rowMin = $stmtMinNovo->fetch(PDO::FETCH_ASSOC);
        $minNovo = isset($rowMin['min_novo']) ? (int)$rowMin['min_novo'] : null;

        if ($minNovo) {
            foreach ($fkTables as $fk) {
                $tFk = $fk['nome'];
                $campo = $fk['campo'];
                $sqlUpdateFk = "
                    UPDATE `{$tFk}`
                    SET `{$campo}` = {$minNovo}
                    WHERE `{$campo}` = 0
                ";
                $afetadosFk = $conn->exec($sqlUpdateFk);
                if ($afetadosFk > 0) {
                    echo "  -> FKs atualizadas em {$tFk}.{$campo}: {$afetadosFk} registros com 0 agora apontam para {$minNovo}\n";
                }
            }
        }

        $conn->commit();
        echo "  -> Treinamentos atualizados: {$afetadosTrainings}\n\n";
    } else {
        $conn->commit();
        echo "Tabela adms_trainings: nenhum registro com id = 0. Nada a fazer.\n";
    }
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "[ERRO] Falha ao tratar adms_trainings: " . $e->getMessage() . "\n";
}

/**
 * Tabela lgpd_consentimentos: corrigir id = 0 e ajustar FK
 * em lgpd_consentimentos_historico (campo lgpd_consentimento_id).
 */
try {
    $table = 'lgpd_consentimentos';
    $conn->beginTransaction();

    $stmtZeros = $conn->query("SELECT id FROM `{$table}` WHERE id = 0");
    $rowsZeros = $stmtZeros->fetchAll(PDO::FETCH_ASSOC);
    $qtd = count($rowsZeros);

    if ($qtd > 0) {
        $stmtMax = $conn->query("SELECT IFNULL(MAX(id), 0) AS max_id FROM `{$table}` WHERE id <> 0");
        $rowMax = $stmtMax->fetch(PDO::FETCH_ASSOC);
        $maxId = isset($rowMax['max_id']) ? (int)$rowMax['max_id'] : 0;

        echo "Tabela {$table}: {$qtd} registros com id = 0. max_id atual = {$maxId}. Regenerando com ajuste de FKs...\n";

        $sqlSet = "SET @max_id := {$maxId}";
        $conn->exec($sqlSet);

        $sqlUpdateConsent = "
            UPDATE `{$table}`
            SET id = (@max_id := @max_id + 1)
            WHERE id = 0
        ";
        $afetadosConsent = $conn->exec($sqlUpdateConsent);

        $stmtMinNovo = $conn->query("SELECT MIN(id) AS min_novo FROM `{$table}` WHERE id > {$maxId}");
        $rowMin = $stmtMinNovo->fetch(PDO::FETCH_ASSOC);
        $minNovo = isset($rowMin['min_novo']) ? (int)$rowMin['min_novo'] : null;

        if ($minNovo) {
            $sqlUpdateHist = "
                UPDATE `lgpd_consentimentos_historico`
                SET lgpd_consentimento_id = {$minNovo}
                WHERE lgpd_consentimento_id = 0
            ";
            $afetadosHist = $conn->exec($sqlUpdateHist);
            if ($afetadosHist > 0) {
                echo "  -> FKs atualizadas em lgpd_consentimentos_historico.lgpd_consentimento_id: {$afetadosHist} registros com 0 agora apontam para {$minNovo}\n";
            }
        }

        $conn->commit();
        echo "  -> Consentimentos atualizados: {$afetadosConsent}\n\n";
    } else {
        $conn->commit();
        echo "Tabela lgpd_consentimentos: nenhum registro com id = 0. Nada a fazer.\n";
    }
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "[ERRO] Falha ao tratar lgpd_consentimentos: " . $e->getMessage() . "\n";
}

echo "=== Fim da regeneração de IDs iguais a 0 ===\n";


