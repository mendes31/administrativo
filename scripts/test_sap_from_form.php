<?php
/**
 * Teste simulando o que vem do formulário
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

// Simular o que vem do POST do formulário
$_POST = [
    'report_id' => 'preview',
    'custom_sql' => '1 SELECT * FROM OITM',  // Com o número 1 como no editor
    'query_mode' => 'custom_sql',
    'visualization_type' => 'table',
    'force_refresh' => false
];

echo "=== TESTE SIMULANDO FORMULÁRIO ===\n\n";

echo "1. SQL do POST (raw):\n";
echo "   [" . $_POST['custom_sql'] . "]\n";
echo "   Length: " . strlen($_POST['custom_sql']) . "\n";
echo "   Hex: " . bin2hex(substr($_POST['custom_sql'], 0, 30)) . "\n\n";

// Testar o serviço
echo "2. Testando DynamicQueryBuilderService:\n";
try {
    require_once __DIR__ . '/../app/adms/Models/Services/DynamicQueryBuilderService.php';
    
    $config = [
        'custom_sql' => $_POST['custom_sql'],
        'query_mode' => 'custom_sql',
        'force_refresh' => true, // Forçar para não usar cache
        'cache_namespace' => 'test'
    ];
    
    $builder = new \App\adms\Models\Services\DynamicQueryBuilderService();
    $result = $builder->executeReport($config);
    
    if ($result['success']) {
        echo "   ✅ Sucesso!\n";
        echo "   Registros: " . ($result['rows_count'] ?? 0) . "\n";
        echo "   Conexão: " . ($result['connection_type'] ?? 'N/A') . "\n";
        echo "   Tempo: " . ($result['execution_time'] ?? 'N/A') . "s\n";
    } else {
        echo "   ❌ Falhou!\n";
        echo "   Erro: " . ($result['error'] ?? 'Erro desconhecido') . "\n";
        echo "   Conexão: " . ($result['connection_type'] ?? 'N/A') . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ EXCEÇÃO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";

