<?php
/**
 * Script para testar getDepartmentStatistics() e verificar logs
 * 
 * Uso: php scripts/test_dept_stats_logs.php
 */

require __DIR__ . '/../vendor/autoload.php';

// Configurar error_log para arquivo
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

echo "=== TESTE: getDepartmentStatistics() com logs ===\n\n";

try {
    $controller = new \App\adms\Controllers\trainings\TrainingKpiDashboard();
    
    // Usar Reflection para acessar método privado
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('getDepartmentStatistics');
    $method->setAccessible(true);
    
    echo "Executando getDepartmentStatistics()...\n";
    $data = $method->invoke($controller);
    
    echo "\n=== RESULTADO ===\n";
    echo "Total de departamentos: " . count($data) . "\n\n";
    
    // Mostrar primeiros 5 departamentos
    echo "Primeiros 5 departamentos:\n";
    echo str_repeat("=", 80) . "\n";
    foreach (array_slice($data, 0, 5) as $dept) {
        echo sprintf(
            "ID: %d | %s\n",
            $dept['department_id'] ?? 'N/A',
            $dept['department_name'] ?? 'N/A'
        );
        echo sprintf(
            "  Total: %d | Concluídos: %d | Em Dia: %d | Pendentes: %d | Vencidos: %d | Agendados: %d\n",
            $dept['total_vinculos'] ?? 0,
            $dept['concluidos'] ?? 0,
            $dept['em_dia'] ?? 0,
            $dept['pendentes'] ?? 0,
            $dept['vencidos'] ?? 0,
            $dept['agendados'] ?? 0
        );
        echo "\n";
    }
    
    // Verificar se há departamentos com valores zerados mas que deveriam ter valores
    echo "\n=== VERIFICAÇÃO: Departamentos com total > 0 mas indicadores zerados ===\n";
    $problemas = [];
    foreach ($data as $dept) {
        $total = $dept['total_vinculos'] ?? 0;
        $emDia = $dept['em_dia'] ?? 0;
        $pendentes = $dept['pendentes'] ?? 0;
        $vencidos = $dept['vencidos'] ?? 0;
        $agendados = $dept['agendados'] ?? 0;
        $concluidos = $dept['concluidos'] ?? 0;
        
        $somaIndicadores = $emDia + $pendentes + $vencidos + $agendados + $concluidos;
        
        if ($total > 0 && $somaIndicadores == 0) {
            $problemas[] = [
                'department' => $dept['department_name'] ?? 'N/A',
                'total' => $total,
                'soma_indicadores' => $somaIndicadores
            ];
        }
    }
    
    if (!empty($problemas)) {
        echo "⚠️  ATENÇÃO: Encontrados " . count($problemas) . " departamentos com problemas:\n";
        foreach ($problemas as $p) {
            echo sprintf("  - %s: Total = %d, Soma Indicadores = %d\n", $p['department'], $p['total'], $p['soma_indicadores']);
        }
    } else {
        echo "✓ Nenhum problema encontrado.\n";
    }
    
    echo "\n=== FIM DO TESTE ===\n";
    echo "\nVerifique os logs em: logs/php_errors.log\n";
    echo "Comando: tail -f logs/php_errors.log\n";
    
} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

