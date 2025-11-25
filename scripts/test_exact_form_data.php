<?php
/**
 * Teste com dados exatos do formulário
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

// Simular exatamente o que vem do POST (com possível número no início)
$testCases = [
    '1 SELECT * FROM OITM',
    'SELECT * FROM OITM',
    ' 1 SELECT * FROM OITM ',
    "1\tSELECT * FROM OITM",
];

echo "=== TESTE COM DADOS DO FORMULÁRIO ===\n\n";

foreach ($testCases as $index => $originalSql) {
    echo "Teste " . ($index + 1) . ": [{$originalSql}]\n";
    echo "Length: " . strlen($originalSql) . "\n";
    echo "Hex: " . bin2hex($originalSql) . "\n";
    
    // Processar como o código faz
    $sql = trim($originalSql);
    $sql = preg_replace('/^[\d\s]+/i', '', $sql);
    $sql = trim($sql);
    
    echo "Após processamento: [{$sql}]\n";
    echo "Match SELECT: " . (preg_match('/^\s*SELECT\s+/i', $sql) ? 'SIM' : 'NÃO') . "\n";
    
    if (preg_match('/^\s*SELECT\s+/i', $sql)) {
        // Testar a URL
        $baseUrl = rtrim($_ENV['SAP_REPORT_API_URL'], '/');
        $encoded = rawurlencode($sql);
        $url = $baseUrl . '?sql=' . $encoded;
        
        echo "URL: {$url}\n";
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'ngrok-skip-browser-warning: true'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "HTTP: {$httpCode}\n";
        if ($httpCode !== 200) {
            echo "Erro: " . substr($response, 0, 200) . "\n";
        } else {
            echo "✅ Sucesso!\n";
        }
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

