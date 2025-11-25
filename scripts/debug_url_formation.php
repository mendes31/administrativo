<?php
/**
 * Debug: Como a URL está sendo formada
 */

require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

$sql = 'SELECT * FROM OITM';
echo "=== COMPARAÇÃO DE URLS ===\n\n";

echo "1. SQL Original: [{$sql}]\n\n";

// Simular o que o serviço faz
$sql = trim($sql);
$sql = preg_replace('/^[\d\s]+/i', '', $sql);
$sql = trim($sql);

echo "2. SQL Após limpeza: [{$sql}]\n\n";

$baseUrl = $_ENV['SAP_REPORT_API_URL'] ?? 'https://02eb453e3bb4.ngrok-free.app/query';
$baseUrl = rtrim($baseUrl, '/');

echo "3. Base URL: {$baseUrl}\n\n";

// Teste 1: rawurlencode (o que o código usa)
$encoded1 = rawurlencode($sql);
$url1 = $baseUrl . '?sql=' . $encoded1;

echo "4. URL com rawurlencode:\n";
echo "   {$url1}\n\n";

// Teste 2: urlencode (alternativa)
$encoded2 = urlencode($sql);
$url2 = $baseUrl . '?sql=' . $encoded2;

echo "5. URL com urlencode:\n";
echo "   {$url2}\n\n";

// Teste 3: URL que funciona (do usuário)
$url3 = 'https://02eb453e3bb4.ngrok-free.app/query?sql=select%20*%20from%20OITM';
echo "6. URL que funciona (do usuário):\n";
echo "   {$url3}\n\n";

// Comparar
echo "=== COMPARAÇÃO ===\n";
echo "Encoded (rawurlencode): {$encoded1}\n";
echo "Encoded (urlencode):    {$encoded2}\n";
echo "URL que funciona:       select%20*%20from%20OITM\n\n";

// Testar ambas
echo "=== TESTANDO AMBAS ===\n\n";

// Teste com rawurlencode
$ch1 = curl_init($url1);
curl_setopt_array($ch1, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'ngrok-skip-browser-warning: true'
    ]
]);
$r1 = curl_exec($ch1);
$code1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
curl_close($ch1);

echo "rawurlencode - HTTP: {$code1}\n";

// Teste com URL que funciona
$ch2 = curl_init($url3);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'ngrok-skip-browser-warning: true'
    ]
]);
$r2 = curl_exec($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "URL que funciona - HTTP: {$code2}\n\n";

if ($code1 !== 200) {
    echo "❌ rawurlencode falhou!\n";
    echo "Response: " . substr($r1, 0, 200) . "\n\n";
}

if ($code2 === 200) {
    echo "✅ URL que funciona retornou 200!\n";
    echo "Diferença: A URL que funciona usa minúsculas (select) e não (SELECT)\n";
}

