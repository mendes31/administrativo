<?php
/**
 * Teste direto da API - mostra tudo que está sendo enviado
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

$baseUrl = $_ENV['SAP_REPORT_API_URL'] ?? 'https://02eb453e3bb4.ngrok-free.app/query';
$baseUrl = rtrim($baseUrl, '/');

$sql = 'SELECT * FROM OITM';

echo "=== TESTE DIRETO DA API ===\n\n";
echo "Base URL: {$baseUrl}\n";
echo "SQL Original: [{$sql}]\n\n";

// Processar SQL como o serviço faz
$sql = trim($sql);
$sql = preg_replace('/^[\d\s]+/i', '', $sql);
$sql = trim($sql);

echo "SQL Após processamento: [{$sql}]\n\n";

// Encodar
$encodedSql = rawurlencode($sql);
$url = $baseUrl . '?sql=' . $encodedSql;

echo "SQL Encoded: {$encodedSql}\n";
echo "URL Completa: {$url}\n\n";

// Preparar cURL
$ch = curl_init($url);

$headers = [
    'Accept: application/json',
    'ngrok-skip-browser-warning: true',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
];

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 600,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_VERBOSE => true, // Para ver detalhes
]);

echo "=== ENVIANDO REQUISIÇÃO ===\n";
echo "Headers enviados:\n";
foreach ($headers as $header) {
    echo "  - {$header}\n";
}
echo "\n";

// Capturar verbose output
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);

// Ler verbose
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fclose($verbose);

curl_close($ch);

echo "=== RESULTADO ===\n";
echo "HTTP Code: {$httpCode}\n";
if ($error) {
    echo "cURL Error: {$error}\n";
}
echo "\n";

echo "=== INFORMAÇÕES DA REQUISIÇÃO ===\n";
echo "URL Efetiva: " . ($info['url'] ?? 'N/A') . "\n";
echo "Tempo Total: " . ($info['total_time'] ?? 'N/A') . "s\n";
echo "Tamanho Download: " . ($info['size_download'] ?? 'N/A') . " bytes\n";
echo "\n";

if ($verboseLog) {
    echo "=== VERBOSE OUTPUT (cURL) ===\n";
    echo $verboseLog . "\n";
}

echo "=== RESPOSTA DA API ===\n";
if ($httpCode === 200) {
    echo "✅ SUCESSO!\n";
    $data = json_decode($response, true);
    if ($data) {
        echo "Tipo: JSON\n";
        if (isset($data['data'])) {
            echo "Registros retornados: " . count($data['data']) . "\n";
            if (count($data['data']) > 0) {
                echo "\nPrimeiro registro:\n";
                print_r(array_slice($data['data'], 0, 1));
            }
        } else {
            echo "Estrutura da resposta:\n";
            print_r(array_slice($data, 0, 3));
        }
    } else {
        echo "Resposta (primeiros 500 chars):\n";
        echo substr($response, 0, 500) . "\n";
    }
} else {
    echo "❌ ERRO HTTP {$httpCode}\n";
    echo "Resposta completa:\n";
    echo $response . "\n";
}

// Testar também com a URL que funciona (minúsculas)
echo "\n\n=== TESTE COM URL QUE FUNCIONA (minúsculas) ===\n";
$url2 = 'https://02eb453e3bb4.ngrok-free.app/query?sql=select%20*%20from%20OITM';
echo "URL: {$url2}\n";

$ch2 = curl_init($url2);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => $headers,
]);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Code: {$httpCode2}\n";
if ($httpCode2 === 200) {
    echo "✅ FUNCIONA!\n";
    $data2 = json_decode($response2, true);
    if ($data2 && isset($data2['data'])) {
        echo "Registros: " . count($data2['data']) . "\n";
    }
} else {
    echo "❌ ERRO HTTP {$httpCode2}\n";
    echo "Resposta: " . substr($response2, 0, 200) . "\n";
}

