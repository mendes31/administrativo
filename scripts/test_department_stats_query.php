<?php
/**
 * Script de teste para diagnosticar por que os indicadores estão zerados
 * na tabela "Estatísticas por Departamento"
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\adms\Models\Repository\TrainingUsersRepository;

$repo = new TrainingUsersRepository();
$pdo = $repo->getConnection();

echo "=== DIAGNÓSTICO: Estatísticas por Departamento ===\n\n";

// 1. Verificar quais status existem no banco (geral)
echo "1. Status existentes no banco (geral):\n";
$sql1 = "SELECT 
    COALESCE(tu.status, 'NULL') AS status,
    COUNT(*) AS total
FROM adms_training_users tu
INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
GROUP BY tu.status
ORDER BY total DESC";

$stmt1 = $pdo->prepare($sql1);
$stmt1->execute();
$statusGeral = $stmt1->fetchAll(PDO::FETCH_ASSOC);

foreach ($statusGeral as $row) {
    echo "   Status '{$row['status']}': {$row['total']} registros\n";
}

// 2. Verificar status por departamento
echo "\n2. Status por departamento (primeiros 20):\n";
$sql2 = "SELECT 
    d.id AS department_id,
    d.name AS department_name,
    COALESCE(tu.status, 'NULL') AS status,
    COUNT(*) AS total
FROM adms_training_users tu
INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
INNER JOIN adms_departments d ON u.user_department_id = d.id
GROUP BY d.id, d.name, tu.status
ORDER BY d.name, tu.status
LIMIT 20";

$stmt2 = $pdo->prepare($sql2);
$stmt2->execute();
$statusPorDept = $stmt2->fetchAll(PDO::FETCH_ASSOC);

foreach ($statusPorDept as $row) {
    echo "   {$row['department_name']} (ID: {$row['department_id']}): Status '{$row['status']}' = {$row['total']}\n";
}

// 3. Comparar contagem total vs. por departamento
echo "\n3. Comparação: Total geral vs. Soma por departamento:\n";

// Total geral
$sql3a = "SELECT 
    COUNT(*) as total_entries,
    SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo') THEN 1 ELSE 0 END) as em_dia,
    SUM(CASE WHEN tu.status = 'proximo_vencimento' THEN 1 ELSE 0 END) as pendentes,
    SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) as vencidos,
    SUM(CASE WHEN tu.status = 'agendado' THEN 1 ELSE 0 END) as agendados
FROM adms_training_users tu
INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1";

$stmt3a = $pdo->prepare($sql3a);
$stmt3a->execute();
$totalGeral = $stmt3a->fetch(PDO::FETCH_ASSOC);

echo "   Total Geral:\n";
echo "     Total: {$totalGeral['total_entries']}\n";
echo "     Em Dia: {$totalGeral['em_dia']}\n";
echo "     Pendentes: {$totalGeral['pendentes']}\n";
echo "     Vencidos: {$totalGeral['vencidos']}\n";
echo "     Agendados: {$totalGeral['agendados']}\n";

// Soma por departamento
$sql3b = "SELECT 
    SUM(total_entries) as total_entries,
    SUM(em_dia) as em_dia,
    SUM(pendentes) as pendentes,
    SUM(vencidos) as vencidos,
    SUM(agendados) as agendados
FROM (
    SELECT 
        d.id as department_id,
        COUNT(*) as total_entries,
        SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo') THEN 1 ELSE 0 END) as em_dia,
        SUM(CASE WHEN tu.status = 'proximo_vencimento' THEN 1 ELSE 0 END) as pendentes,
        SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) as vencidos,
        SUM(CASE WHEN tu.status = 'agendado' THEN 1 ELSE 0 END) as agendados
    FROM adms_training_users tu
    INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
    INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
    INNER JOIN adms_departments d ON u.user_department_id = d.id
    GROUP BY d.id
) as dept_stats";

$stmt3b = $pdo->prepare($sql3b);
$stmt3b->execute();
$totalPorDept = $stmt3b->fetch(PDO::FETCH_ASSOC);

echo "\n   Soma por Departamento:\n";
echo "     Total: {$totalPorDept['total_entries']}\n";
echo "     Em Dia: {$totalPorDept['em_dia']}\n";
echo "     Pendentes: {$totalPorDept['pendentes']}\n";
echo "     Vencidos: {$totalPorDept['vencidos']}\n";
echo "     Agendados: {$totalPorDept['agendados']}\n";

// 4. Verificar registros sem departamento
echo "\n4. Registros sem departamento:\n";
$sql4 = "SELECT 
    COUNT(*) as registros_sem_departamento
FROM adms_training_users tu
INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
WHERE u.user_department_id IS NULL";

$stmt4 = $pdo->prepare($sql4);
$stmt4->execute();
$semDept = $stmt4->fetch(PDO::FETCH_ASSOC);

echo "   Registros sem departamento: {$semDept['registros_sem_departamento']}\n";

// 5. Testar a query exata usada no dashboard
echo "\n5. Resultado da query exata do dashboard (primeiros 5 departamentos):\n";
$sql5 = "SELECT 
    d.id as department_id,
    d.name as department_name,
    COUNT(*) as total_entries,
    SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo') THEN 1 ELSE 0 END) as em_dia,
    SUM(CASE WHEN tu.status = 'proximo_vencimento' THEN 1 ELSE 0 END) as pendentes,
    SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) as vencidos,
    SUM(CASE WHEN tu.status = 'agendado' THEN 1 ELSE 0 END) as agendados
FROM adms_training_users tu
INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
INNER JOIN adms_departments d ON u.user_department_id = d.id
GROUP BY d.id, d.name
ORDER BY total_entries DESC
LIMIT 5";

$stmt5 = $pdo->prepare($sql5);
$stmt5->execute();
$resultadoDashboard = $stmt5->fetchAll(PDO::FETCH_ASSOC);

foreach ($resultadoDashboard as $row) {
    echo "   {$row['department_name']} (ID: {$row['department_id']}):\n";
    echo "     Total: {$row['total_entries']}\n";
    echo "     Em Dia: {$row['em_dia']}\n";
    echo "     Pendentes: {$row['pendentes']}\n";
    echo "     Vencidos: {$row['vencidos']}\n";
    echo "     Agendados: {$row['agendados']}\n";
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";

