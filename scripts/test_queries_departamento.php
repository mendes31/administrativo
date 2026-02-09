<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\adms\Models\Repository\TrainingUsersRepository;

$repo = new TrainingUsersRepository();
$pdo = $repo->getConnection();
$hoje = date('Y-m-d');

echo "=== TESTE QUERIES ESTATÍSTICAS POR DEPARTAMENTO ===\n\n";
echo "Data de hoje: $hoje\n\n";

// Query 1: Buscar dados para cálculo dinâmico
echo "=== QUERY 1: Dados para cálculo dinâmico ===\n";
$sql1 = "SELECT 
            d.id as department_id,
            d.name as department_name,
            tu.id as training_user_id,
            tu.data_limite_primeiro_treinamento,
            tu.data_agendada,
            tu.tipo_vinculo,
            t.prazo_treinamento,
            ta_last.data_realizacao
        FROM adms_training_users tu
        INNER JOIN adms_users u 
            ON u.id = tu.adms_user_id 
           AND u.status = 'Ativo'
        INNER JOIN adms_trainings t 
            ON t.id = tu.adms_training_id 
           AND t.ativo = 1
        INNER JOIN adms_departments d ON u.user_department_id = d.id
        LEFT JOIN (
            SELECT 
                ta1.adms_user_id,
                ta1.adms_training_id,
                ta1.data_realizacao,
                ta1.created_at
            FROM adms_training_applications ta1
            INNER JOIN (
                SELECT 
                    adms_user_id,
                    adms_training_id,
                    MAX(created_at) as max_created_at
                FROM adms_training_applications
                GROUP BY adms_user_id, adms_training_id
            ) ta2 ON ta1.adms_user_id = ta2.adms_user_id 
                AND ta1.adms_training_id = ta2.adms_training_id 
                AND ta1.created_at = ta2.max_created_at
        ) ta_last ON ta_last.adms_user_id = tu.adms_user_id 
            AND ta_last.adms_training_id = tu.adms_training_id
            AND (ta_last.created_at >= tu.created_at OR ta_last.created_at IS NULL)
        LIMIT 10";

$stmt1 = $pdo->prepare($sql1);
$stmt1->execute();
$rows1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

echo "Total de registros encontrados: " . count($rows1) . "\n";
echo "Primeiros 5 registros:\n";
foreach (array_slice($rows1, 0, 5) as $i => $row) {
    echo "\nRegistro " . ($i+1) . ":\n";
    echo "  Departamento: {$row['department_name']} (ID: {$row['department_id']})\n";
    echo "  data_limite: " . ($row['data_limite_primeiro_treinamento'] ?? 'NULL') . "\n";
    echo "  data_agendada: " . ($row['data_agendada'] ?? 'NULL') . "\n";
    echo "  data_realizacao: " . ($row['data_realizacao'] ?? 'NULL') . "\n";
    echo "  prazo_treinamento: " . ($row['prazo_treinamento'] ?? 'NULL') . "\n";
    echo "  tipo_vinculo: " . ($row['tipo_vinculo'] ?? 'NULL') . "\n";
    
    if ($row['data_limite_primeiro_treinamento']) {
        $dias = round((strtotime($row['data_limite_primeiro_treinamento']) - strtotime($hoje)) / (60 * 60 * 24), 1);
        echo "  diasParaPrazo: $dias\n";
    }
}

// Contar por departamento
echo "\n\n=== CONTAGEM POR DEPARTAMENTO (Query 1) ===\n";
$sqlCount = "SELECT 
                d.id as department_id,
                d.name as department_name,
                COUNT(*) as total
            FROM adms_training_users tu
            INNER JOIN adms_users u 
                ON u.id = tu.adms_user_id 
               AND u.status = 'Ativo'
            INNER JOIN adms_trainings t 
                ON t.id = tu.adms_training_id 
               AND t.ativo = 1
            INNER JOIN adms_departments d ON u.user_department_id = d.id
            GROUP BY d.id, d.name
            ORDER BY total DESC
            LIMIT 10";

$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute();
$counts = $stmtCount->fetchAll(PDO::FETCH_ASSOC);

foreach ($counts as $count) {
    echo "  {$count['department_name']} (ID: {$count['department_id']}): {$count['total']} registros\n";
}

// Query 2: Concluídos
echo "\n\n=== QUERY 2: Concluídos por departamento ===\n";
$sql2 = "SELECT 
            d.id as department_id,
            COUNT(*) as concluidos
        FROM adms_training_users tu
        LEFT JOIN adms_users u ON u.id = tu.adms_user_id
        LEFT JOIN adms_trainings t ON t.id = tu.adms_training_id
        LEFT JOIN adms_departments d ON u.user_department_id = d.id
        LEFT JOIN (
            SELECT 
                ta1.adms_user_id,
                ta1.adms_training_id,
                ta1.data_realizacao
            FROM adms_training_applications ta1
            INNER JOIN (
                SELECT 
                    adms_user_id,
                    adms_training_id,
                    MAX(created_at) as max_created_at
                FROM adms_training_applications
                GROUP BY adms_user_id, adms_training_id
            ) ta2 ON ta1.adms_user_id = ta2.adms_user_id 
                AND ta1.adms_training_id = ta2.adms_training_id 
                AND ta1.created_at = ta2.max_created_at
        ) ta_last ON ta_last.adms_user_id = tu.adms_user_id 
            AND ta_last.adms_training_id = tu.adms_training_id
        WHERE ta_last.data_realizacao IS NOT NULL
          AND u.id IS NOT NULL
          AND t.id IS NOT NULL
          AND d.id IS NOT NULL
        GROUP BY d.id
        ORDER BY concluidos DESC
        LIMIT 10";

$stmt2 = $pdo->prepare($sql2);
$stmt2->execute();
$concluidos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "Total de departamentos com concluídos: " . count($concluidos) . "\n";
foreach ($concluidos as $conc) {
    echo "  Departamento ID {$conc['department_id']}: {$conc['concluidos']} concluídos\n";
}

// Verificar status no banco
echo "\n\n=== VERIFICAR STATUS NO BANCO (tu.status) ===\n";
$sqlStatus = "SELECT 
                tu.status,
                COUNT(*) as count
            FROM adms_training_users tu
            INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
            INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
            GROUP BY tu.status
            ORDER BY count DESC";

$stmtStatus = $pdo->prepare($sqlStatus);
$stmtStatus->execute();
$statusCounts = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

foreach ($statusCounts as $sc) {
    echo "  Status '{$sc['status']}': {$sc['count']} registros\n";
}

