<?php
/**
 * Script para testar diretamente o método getDepartmentStatistics()
 * e verificar o que está sendo retornado
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\adms\Controllers\trainings\TrainingKpiDashboard;

echo "=== TESTE DIRETO: getDepartmentStatistics() ===\n\n";

try {
    $controller = new TrainingKpiDashboard();
    
    // Usar Reflection para acessar método privado
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('getDepartmentStatistics');
    $method->setAccessible(true);
    
    echo "Executando getDepartmentStatistics()...\n\n";
    $result = $method->invoke($controller);
    
    echo "Total de departamentos retornados: " . count($result) . "\n\n";
    
    if (empty($result)) {
        echo "❌ ERRO: Nenhum departamento retornado!\n";
        exit(1);
    }
    
    echo "Primeiros 5 departamentos:\n";
    echo str_repeat("=", 80) . "\n";
    
    foreach (array_slice($result, 0, 5) as $dept) {
        echo "Departamento: {$dept['department_name']} (ID: {$dept['department_id']})\n";
        echo "  Total: {$dept['total_vinculos']}\n";
        echo "  Concluídos: {$dept['concluidos']}\n";
        echo "  Em Dia: {$dept['em_dia']}\n";
        echo "  Pendentes: {$dept['pendentes']}\n";
        echo "  Vencidos: {$dept['vencidos']}\n";
        echo "  Agendados: {$dept['agendados']}\n";
        echo "\n";
    }
    
    // Verificar se há departamentos com valores zerados incorretamente
    echo "Verificando departamentos com valores zerados (mas que deveriam ter valores):\n";
    echo str_repeat("=", 80) . "\n";
    
    $problemas = [];
    foreach ($result as $dept) {
        if ($dept['total_vinculos'] > 0) {
            // Se tem total mas em_dia e pendentes estão zerados, pode ser problema
            if ($dept['em_dia'] == 0 && $dept['pendentes'] == 0 && 
                $dept['vencidos'] == 0 && $dept['agendados'] == 0 && 
                $dept['concluidos'] == 0) {
                $problemas[] = $dept;
            }
        }
    }
    
    if (!empty($problemas)) {
        echo "⚠️  ATENÇÃO: Encontrados " . count($problemas) . " departamentos com total > 0 mas todos os indicadores zerados:\n";
        foreach ($problemas as $dept) {
            echo "  - {$dept['department_name']}: Total = {$dept['total_vinculos']}\n";
        }
    } else {
        echo "✅ Nenhum problema encontrado - todos os departamentos têm valores corretos\n";
    }
    
    echo "\n=== FIM DO TESTE ===\n";
    
} catch (\Exception $e) {
    echo "❌ ERRO ao executar teste:\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

