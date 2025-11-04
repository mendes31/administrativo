<?php
echo "\n🧪 TESTE DIRETO DO ENDPOINT ExecuteDynamicReport\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$url = "http://192.168.3.38/administrativo/execute-dynamic-report";

$data = [
    'report_id' => 'preview',
    'query_mode' => 'custom_sql',
    'custom_sql' => 'SELECT TOP 5 "ItemCode", "ItemName" FROM OITM',
    'visualization_type' => 'table'
];

echo "📝 Dados enviados:\n";
echo "  URL: $url\n";
echo "  SQL: {$data['custom_sql']}\n\n";

echo "📊 Fazendo requisição...\n";
echo "───────────────────────────────────────────────────────────────\n";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($data),
    CURLOPT_HEADER => true,
    CURLOPT_VERBOSE => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "HTTP Code: $httpCode\n\n";

echo "Headers:\n";
echo str_repeat('-', 60) . "\n";
echo $headers;
echo str_repeat('-', 60) . "\n\n";

echo "Body (primeiros 500 caracteres):\n";
echo str_repeat('-', 60) . "\n";
echo substr($body, 0, 500);
echo str_repeat('-', 60) . "\n\n";

// Tentar decodificar JSON
$json = json_decode($body, true);

if ($json) {
    echo "✅ JSON válido!\n\n";
    print_r($json);
} else {
    echo "❌ JSON INVÁLIDO!\n";
    echo "Erro: " . json_last_error_msg() . "\n\n";
    
    echo "Corpo completo da resposta:\n";
    echo str_repeat('=', 60) . "\n";
    echo $body;
    echo "\n" . str_repeat('=', 60) . "\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";

