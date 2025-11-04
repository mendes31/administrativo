<?php
// Simular ambiente web
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'report_id' => 'preview',
    'query_mode' => 'custom_sql',
    'custom_sql' => 'SELECT TOP 5 "ItemCode", "ItemName" FROM OITM',
    'visualization_type' => 'table'
];

// Capturar output
ob_start();

try {
    require __DIR__ . '/../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
    $dotenv->load();
    
    // Simular sessão
    session_start();
    $_SESSION['user_id'] = 1;
    
    echo "\n🧪 TESTE DIRETO - ExecuteDynamicReport\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    echo "📝 Criando controller...\n";
    $controller = new \App\adms\Controllers\reports\ExecuteDynamicReport();
    
    echo "📊 Executando index()...\n";
    $controller->index();
    
    $output = ob_get_clean();
    
    echo "\n✅ Output capturado:\n";
    echo str_repeat('-', 60) . "\n";
    echo $output;
    echo str_repeat('-', 60) . "\n\n";
    
    // Tentar decodificar JSON
    $json = json_decode($output, true);
    
    if ($json) {
        echo "✅ JSON VÁLIDO!\n\n";
        print_r($json);
    } else {
        echo "❌ JSON INVÁLIDO: " . json_last_error_msg() . "\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "\n❌ EXCEÇÃO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";

