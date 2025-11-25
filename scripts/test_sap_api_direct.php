<?php
/**
 * Teste direto da API SAP - Debug completo
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../app/logs/sap_api_test.log');

require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

echo "=== TESTE DIRETO API SAP ===\n\n";

// Verificar variáveis de ambiente
echo "1. Verificando variáveis de ambiente:\n";
$apiUrl = $_ENV['SAP_REPORT_API_URL'] ?? null;
echo "   SAP_REPORT_API_URL: " . ($apiUrl ?: 'NÃO DEFINIDA') . "\n";
if (!$apiUrl) {
    echo "   ❌ ERRO: Variável SAP_REPORT_API_URL não está definida no .env!\n";
    exit(1);
}

// Testar o serviço diretamente
echo "\n2. Testando SapReportApiService:\n";
try {
    require_once __DIR__ . '/../app/adms/Models/Services/SapReportApiService.php';
    $service = new \App\adms\Models\Services\SapReportApiService();
    echo "   ✅ Serviço instanciado\n";
    
    $sql = 'SELECT * FROM OITM LIMIT 100';
    echo "   SQL: {$sql}\n";
    echo "   Executando...\n";
    
    // Testar diretamente com cURL para ver a resposta completa
    $baseUrl = $_ENV['SAP_REPORT_API_URL'];
    $url = rtrim($baseUrl, '/') . '?sql=' . rawurlencode($sql);
    
    echo "   URL: {$url}\n";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'ngrok-skip-browser-warning: true',
            'User-Agent: PHP-SAP-Report-Client/1.0'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   HTTP Code: {$httpCode}\n";
    echo "   Response (primeiros 1000 chars):\n";
    echo "   " . str_replace("\n", "\n   ", substr($response, 0, 1000)) . "\n\n";
    
    if ($httpCode === 200) {
        $result = $service->execute($sql);
        echo "   ✅ Sucesso via serviço!\n";
        echo "   Registros: " . ($result['rows_count'] ?? count($result['data'] ?? [])) . "\n";
    } else {
        echo "   ❌ HTTP {$httpCode} - Ver resposta acima\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ ERRO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Testar o DynamicQueryBuilderService
echo "\n3. Testando DynamicQueryBuilderService:\n";
try {
    require_once __DIR__ . '/../app/adms/Models/Services/DynamicQueryBuilderService.php';
    $builder = new \App\adms\Models\Services\DynamicQueryBuilderService();
    echo "   ✅ Builder instanciado\n";
    
    $config = [
        'custom_sql' => 'SELECT * FROM OITM LIMIT 100',
        'query_mode' => 'custom_sql',
        'force_refresh' => true
    ];
    
    echo "   Executando relatório...\n";
    $result = $builder->executeReport($config);
    
    if ($result['success']) {
        echo "   ✅ Sucesso!\n";
        echo "   Registros: " . ($result['rows_count'] ?? 0) . "\n";
        echo "   Conexão: " . ($result['connection_type'] ?? 'N/A') . "\n";
    } else {
        echo "   ❌ Falhou: " . ($result['error'] ?? 'Erro desconhecido') . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ ERRO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";

