<?php
/**
 * Debug completo da API SAP
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

$sql = 'SELECT * FROM OITM LIMIT 100';
echo "SQL Original: [{$sql}]\n";
echo "SQL Length: " . strlen($sql) . "\n\n";

// Simular o que o serviço faz
$sql = trim($sql);
echo "Após trim: [{$sql}]\n";

$sql = preg_replace('/^[\d\s]+/i', '', $sql);
$sql = trim($sql);
echo "Após remover números: [{$sql}]\n\n";

$baseUrl = $_ENV['SAP_REPORT_API_URL'];
$baseUrl = rtrim($baseUrl, '/');
$encodedSql = rawurlencode($sql);
$url = $baseUrl . '?sql=' . $encodedSql;

echo "Base URL: {$baseUrl}\n";
echo "SQL Encoded: {$encodedSql}\n";
echo "URL Completa: {$url}\n\n";

// Testar
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
echo "\nResponse (primeiros 500 chars):\n";
echo substr($response, 0, 500) . "\n";

if ($httpCode === 400) {
    echo "\n=== ERRO 400 DETALHADO ===\n";
    $json = json_decode($response, true);
    if ($json) {
        print_r($json);
    } else {
        echo "Resposta não é JSON válido\n";
        echo "Resposta completa:\n{$response}\n";
    }
}

