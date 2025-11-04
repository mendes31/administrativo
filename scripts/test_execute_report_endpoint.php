<?php
/**
 * Script para testar o endpoint execute-dynamic-report diretamente
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;

echo "\n🧪 TESTE DO ENDPOINT execute-dynamic-report\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Simular requisição
$_POST['report_id'] = 'preview';
$_POST['data_source'] = 'adms_users';
$_POST['fields'] = json_encode([
    ['field' => 'name'],
    ['field' => 'status']
]);
$_POST['filters'] = '[]';
$_POST['groupby'] = '[]';
$_POST['orderby'] = '[]';
$_POST['visualization_type'] = 'table';

echo "📋 Dados de entrada:\n";
echo "  - data_source: adms_users\n";
echo "  - fields: name, status\n\n";

echo "🔧 Criando configuração de relatório...\n";

$report = [
    'data_source' => $_POST['data_source'],
    'fields' => json_decode($_POST['fields'], true),
    'filters' => json_decode($_POST['filters'] ?? '[]', true),
    'groupby' => json_decode($_POST['groupby'] ?? '[]', true),
    'orderby' => json_decode($_POST['orderby'] ?? '[]', true)
];

print_r($report);

echo "\n⚙️  Executando QueryBuilder...\n";

try {
    $queryBuilder = new DynamicQueryBuilderService();
    $result = $queryBuilder->executeReport($report);
    
    echo "\n✅ Resultado:\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "Success: " . ($result['success'] ? 'SIM' : 'NÃO') . "\n";
    
    if ($result['success']) {
        echo "Registros encontrados: " . $result['rows_count'] . "\n";
        echo "Tempo de execução: " . $result['execution_time'] . "s\n";
        echo "Conexão: " . $result['connection_type'] . "\n";
        echo "SQL: " . $result['sql'] . "\n\n";
        
        echo "📊 Primeiros 5 registros:\n";
        echo "───────────────────────────────────────────────────────────────\n";
        foreach (array_slice($result['data'], 0, 5) as $row) {
            echo "  " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        echo "\n✅ ENDPOINT FUNCIONANDO PERFEITAMENTE!\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
    } else {
        echo "❌ ERRO: " . $result['error'] . "\n";
        if (isset($result['sql'])) {
            echo "SQL: " . $result['sql'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ EXCEÇÃO CAPTURADA:\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

