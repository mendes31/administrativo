<?php
/**
 * Script para testar a query diretamente e ver o que está sendo retornado
 */

require __DIR__ . '/../vendor/autoload.php';

$repo = new \App\adms\Models\Repository\TrainingUsersRepository();
$pdo = $repo->getConnection();

echo "=== TESTE DIRETO DA QUERY ===\n\n";

// Query exata do dashboard
$sqlActive = "SELECT 
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

$stmtActive = $pdo->prepare($sqlActive);
$stmtActive->execute();
$activeStats = $stmtActive->fetchAll(\PDO::FETCH_ASSOC);

echo "Total de linhas retornadas: " . count($activeStats) . "\n\n";

foreach ($activeStats as $index => $row) {
    echo "=== Linha " . ($index + 1) . " ===\n";
    echo "Department ID: " . $row['department_id'] . "\n";
    echo "Department Name: " . $row['department_name'] . "\n";
    echo "Total Entries: " . $row['total_entries'] . " (tipo: " . gettype($row['total_entries']) . ")\n";
    echo "Em Dia: " . var_export($row['em_dia'], true) . " (tipo: " . gettype($row['em_dia']) . ")\n";
    echo "Pendentes: " . var_export($row['pendentes'], true) . " (tipo: " . gettype($row['pendentes']) . ")\n";
    echo "Vencidos: " . var_export($row['vencidos'], true) . " (tipo: " . gettype($row['vencidos']) . ")\n";
    echo "Agendados: " . var_export($row['agendados'], true) . " (tipo: " . gettype($row['agendados']) . ")\n";
    
    // Testar conversão
    $emDia = (int)($row['em_dia'] ?? 0);
    $pendentes = (int)($row['pendentes'] ?? 0);
    $vencidos = (int)($row['vencidos'] ?? 0);
    $agendados = (int)($row['agendados'] ?? 0);
    
    echo "\nApós conversão (int):\n";
    echo "  Em Dia: $emDia\n";
    echo "  Pendentes: $pendentes\n";
    echo "  Vencidos: $vencidos\n";
    echo "  Agendados: $agendados\n";
    
    // Testar função convertSumValue
    $controller = new \App\adms\Controllers\trainings\TrainingKpiDashboard();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('convertSumValue');
    $method->setAccessible(true);
    
    $emDiaConverted = $method->invoke($controller, $row['em_dia'] ?? 0);
    $pendentesConverted = $method->invoke($controller, $row['pendentes'] ?? 0);
    $vencidosConverted = $method->invoke($controller, $row['vencidos'] ?? 0);
    $agendadosConverted = $method->invoke($controller, $row['agendados'] ?? 0);
    
    echo "\nApós convertSumValue:\n";
    echo "  Em Dia: $emDiaConverted\n";
    echo "  Pendentes: $pendentesConverted\n";
    echo "  Vencidos: $vencidosConverted\n";
    echo "  Agendados: $agendadosConverted\n";
    
    echo "\n" . str_repeat("-", 60) . "\n\n";
}

echo "=== FIM DO TESTE ===\n";

