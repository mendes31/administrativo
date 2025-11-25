<?php
require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

$sql = 'SELECT * FROM OITM LIMIT 100';
$baseUrl = $_ENV['SAP_REPORT_API_URL'] ?? 'https://02eb453e3bb4.ngrok-free.app/query';
$url = rtrim($baseUrl, '/') . '?sql=' . rawurlencode($sql);

echo "URL: {$url}\n\n";

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
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
if ($curlError) {
    echo "cURL Error: {$curlError}\n";
}
echo "\nResponse (primeiros 500 chars):\n";
echo substr($response, 0, 500) . "\n";

if ($httpCode === 400) {
    echo "\n=== ERRO 400 DETECTADO ===\n";
    $json = json_decode($response, true);
    if ($json) {
        print_r($json);
    }
}

