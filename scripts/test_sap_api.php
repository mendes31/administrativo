<?php
/**
 * Script de teste para a API SAP de Relatórios
 * 
 * Uso: php scripts/test_sap_api.php
 */

require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

$apiUrl = $_ENV['SAP_REPORT_API_URL'] ?? 'https://02eb453e3bb4.ngrok-free.app/query';
$sql = 'SELECT * FROM OITM';

echo "🧪 TESTE DA API SAP\n";
echo "==================\n\n";
echo "URL Base: {$apiUrl}\n";
echo "SQL: {$sql}\n\n";

// Teste 1: GET com query parameter
echo "📤 Teste 1: GET com ?sql=\n";
$encodedSql = rawurlencode($sql);
$url = rtrim($apiUrl, '/') . '?sql=' . $encodedSql;
echo "URL completa: {$url}\n";

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
    ],
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_VERBOSE => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
if ($curlError) {
    echo "cURL Error: {$curlError}\n";
}
echo "Response (primeiros 500 chars):\n" . substr($response, 0, 500) . "\n\n";

// Tentar parse JSON
$json = json_decode($response, true);
if ($json !== null) {
    echo "✅ Resposta é JSON válido\n";
    echo "Estrutura:\n";
    print_r(array_keys($json));
} else {
    echo "❌ Resposta não é JSON válido\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Teste 2: POST com JSON (caso a API prefira)
echo "📤 Teste 2: POST com JSON body\n";
$url2 = rtrim($apiUrl, '/');
$postData = json_encode(['sql' => $sql]);

$ch2 = curl_init($url2);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'ngrok-skip-browser-warning: true',
        'User-Agent: PHP-SAP-Report-Client/1.0'
    ],
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3
]);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$curlError2 = curl_error($ch2);
curl_close($ch2);

echo "HTTP Code: {$httpCode2}\n";
if ($curlError2) {
    echo "cURL Error: {$curlError2}\n";
}
echo "Response (primeiros 500 chars):\n" . substr($response2, 0, 500) . "\n\n";

if ($httpCode2 === 200) {
    echo "✅ POST funcionou!\n";
    $json2 = json_decode($response2, true);
    if ($json2 !== null) {
        echo "Estrutura:\n";
        print_r(array_keys($json2));
    }
}

