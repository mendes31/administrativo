<?php
/**
 * Script para diagnosticar quais status realmente existem no banco de dados
 * 
 * Uso: php scripts/diagnostico_status_banco.php
 */

require __DIR__ . '/../vendor/autoload.php';

$repo = new \App\adms\Models\Repository\TrainingUsersRepository();
$pdo = $repo->getConnection();

echo "=== DIAGNÓSTICO: Status no Banco de Dados ===\n\n";

// 1. Verificar todos os status únicos no banco
echo "1. Status únicos no banco (todos os registros):\n";
$sql1 = "SELECT 
            COALESCE(tu.status, 'NULL') AS status,
            COUNT(*) AS total
         FROM adms_training_users tu
         GROUP BY tu.status
         ORDER BY total DESC";
$stmt1 = $pdo->prepare($sql1);
$stmt1->execute();
$statusGeral = $stmt1->fetchAll(\PDO::FETCH_ASSOC);
foreach ($statusGeral as $row) {
    echo sprintf("  Status '%s': %d registros\n", $row['status'], $row['total']);
}

// 2. Verificar status apenas para usuários/treinamentos ativos (mesma lógica da query)
echo "\n2. Status únicos (apenas usuários/treinamentos ativos):\n";
$sql2 = "SELECT 
            COALESCE(tu.status, 'NULL') AS status,
            COUNT(*) AS total
         FROM adms_training_users tu
         INNER JOIN adms_users u 
             ON u.id = tu.adms_user_id 
            AND u.status = 'Ativo'
         INNER JOIN adms_trainings t 
             ON t.id = tu.adms_training_id 
            AND t.ativo = 1
         GROUP BY tu.status
         ORDER BY total DESC";
$stmt2 = $pdo->prepare($sql2);
$stmt2->execute();
$statusAtivos = $stmt2->fetchAll(\PDO::FETCH_ASSOC);
foreach ($statusAtivos as $row) {
    echo sprintf("  Status '%s': %d registros\n", $row['status'], $row['total']);
}

// 3. Verificar status agrupados por departamento (primeiros 3 departamentos)
echo "\n3. Status por departamento (primeiros 3 departamentos):\n";
$sql3 = "SELECT 
            d.id AS department_id,
            d.name AS department_name,
            COALESCE(tu.status, 'NULL') AS status,
            COUNT(*) AS total
         FROM adms_training_users tu
         INNER JOIN adms_users u 
             ON u.id = tu.adms_user_id 
            AND u.status = 'Ativo'
         INNER JOIN adms_trainings t 
             ON t.id = tu.adms_training_id 
            AND t.ativo = 1
         INNER JOIN adms_departments d 
             ON u.user_department_id = d.id
         WHERE (tu.status != 'concluido' OR tu.status IS NULL)
         GROUP BY d.id, d.name, tu.status
         ORDER BY d.name, tu.status
         LIMIT 30";
$stmt3 = $pdo->prepare($sql3);
$stmt3->execute();
$statusPorDept = $stmt3->fetchAll(\PDO::FETCH_ASSOC);
$currentDept = null;
foreach ($statusPorDept as $row) {
    if ($currentDept !== $row['department_name']) {
        $currentDept = $row['department_name'];
        echo "\n  Departamento: {$currentDept} (ID: {$row['department_id']})\n";
    }
    echo sprintf("    Status '%s': %d registros\n", $row['status'], $row['total']);
}

// 4. Testar a query exata do dashboard
echo "\n4. Resultado da query exata do dashboard (primeiros 3 departamentos):\n";
$sql4 = "SELECT 
            d.id   AS department_id,
            d.name AS department_name,
            COUNT(*) AS total_entries,
            SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo') THEN 1 ELSE 0 END) AS em_dia,
            SUM(CASE WHEN tu.status = 'proximo_vencimento' THEN 1 ELSE 0 END) AS pendentes,
            SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) AS vencidos,
            SUM(CASE WHEN tu.status = 'agendado' THEN 1 ELSE 0 END) AS agendados
         FROM adms_training_users tu
         INNER JOIN adms_users u 
             ON u.id = tu.adms_user_id 
            AND u.status = 'Ativo'
         INNER JOIN adms_trainings t 
             ON t.id = tu.adms_training_id 
            AND t.ativo = 1
         INNER JOIN adms_departments d 
             ON u.user_department_id = d.id
         WHERE (tu.status != 'concluido' OR tu.status IS NULL)
         GROUP BY d.id, d.name
         ORDER BY total_entries DESC
         LIMIT 3";
$stmt4 = $pdo->prepare($sql4);
$stmt4->execute();
$resultadoQuery = $stmt4->fetchAll(\PDO::FETCH_ASSOC);
foreach ($resultadoQuery as $row) {
    echo sprintf(
        "\n  Departamento: %s (ID: %d)\n",
        $row['department_name'],
        $row['department_id']
    );
    echo sprintf("    Total: %d\n", $row['total_entries']);
    echo sprintf("    Em Dia: %d\n", $row['em_dia']);
    echo sprintf("    Pendentes: %d\n", $row['pendentes']);
    echo sprintf("    Vencidos: %d\n", $row['vencidos']);
    echo sprintf("    Agendados: %d\n", $row['agendados']);
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";

