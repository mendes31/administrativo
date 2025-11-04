<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

echo "\n🔍 DIAGNÓSTICO - SAP SERVICE LAYER\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Verificar variáveis de ambiente
echo "📋 Passo 1: Verificando configuração do .env\n";
echo "───────────────────────────────────────────────────────────────\n";

$configs = [
    'SAP_SL_URL' => $_ENV['SAP_SL_URL'] ?? null,
    'SAP_SL_USERNAME' => $_ENV['SAP_SL_USERNAME'] ?? null,
    'SAP_SL_PASSWORD' => isset($_ENV['SAP_SL_PASSWORD']) ? '***configurada***' : null,
    'SAP_SL_COMPANY' => $_ENV['SAP_SL_COMPANY'] ?? null,
];

$allConfigured = true;
foreach ($configs as $key => $value) {
    if (empty($value)) {
        echo "  ❌ $key: NÃO CONFIGURADA\n";
        $allConfigured = false;
    } else {
        echo "  ✅ $key: $value\n";
    }
}

if (!$allConfigured) {
    echo "\n⚠️  ERRO: Configure todas as variáveis no arquivo .env\n";
    echo "───────────────────────────────────────────────────────────────\n\n";
    echo "Exemplo:\n";
    echo "SAP_SL_URL=https://192.168.1.100:50000/b1s/v1\n";
    echo "SAP_SL_USERNAME=manager\n";
    echo "SAP_SL_PASSWORD=sua_senha\n";
    echo "SAP_SL_COMPANY=SBODEMOUS\n\n";
    exit(1);
}

echo "\n";

// 2. Testar conectividade básica
echo "📊 Passo 2: Testando conectividade com o servidor\n";
echo "───────────────────────────────────────────────────────────────\n";

$url = $_ENV['SAP_SL_URL'];
$metadataUrl = rtrim($url, '/') . '/$metadata';

echo "  URL: $metadataUrl\n";
echo "  Testando conexão... ";

$ch = curl_init($metadataUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode > 0) {
    echo "✅ OK (HTTP $httpCode)\n";
} else {
    echo "❌ FALHOU\n";
    echo "  Erro: $error\n\n";
    echo "⚠️  Possíveis causas:\n";
    echo "  1. Servidor SAP está desligado\n";
    echo "  2. Firewall bloqueando conexão\n";
    echo "  3. URL incorreta\n";
    echo "  4. Service Layer não está instalada/ativa\n\n";
    exit(1);
}

echo "\n";

// 3. Testar login
echo "📊 Passo 3: Testando login no SAP\n";
echo "───────────────────────────────────────────────────────────────\n";

$loginUrl = rtrim($url, '/') . '/Login';
$loginData = json_encode([
    'CompanyDB' => $_ENV['SAP_SL_COMPANY'],
    'UserName' => $_ENV['SAP_SL_USERNAME'],
    'Password' => $_ENV['SAP_SL_PASSWORD']
]);

echo "  Usuário: {$_ENV['SAP_SL_USERNAME']}\n";
echo "  Banco: {$_ENV['SAP_SL_COMPANY']}\n";
echo "  Tentando login... ";

$ch = curl_init($loginUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $loginData,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_HEADER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ SUCESSO!\n\n";
    
    // Verificar se tem session
    preg_match('/B1SESSION=([^;]+)/', $response, $matches);
    if (isset($matches[1])) {
        echo "  🎉 Login realizado com sucesso!\n";
        echo "  Session ID: " . substr($matches[1], 0, 20) . "...\n\n";
    }
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "✅ CONFIGURAÇÃO ESTÁ CORRETA!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    echo "Você pode usar a Service Layer normalmente!\n\n";
    
} else {
    echo "❌ FALHOU (HTTP $httpCode)\n\n";
    
    // Extrair mensagem de erro
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    $errorData = json_decode($body, true);
    
    if (isset($errorData['error'])) {
        echo "  Erro: {$errorData['error']['message']['value']}\n\n";
    }
    
    echo "⚠️  Possíveis causas:\n";
    if ($httpCode === 401) {
        echo "  ❌ Usuário ou senha incorretos\n";
        echo "  ❌ Usuário sem permissão\n";
    } elseif ($httpCode === 404) {
        echo "  ❌ Nome do banco (COMPANY) incorreto\n";
    } else {
        echo "  ❌ Verifique usuário, senha e nome do banco\n";
    }
    echo "\n";
    exit(1);
}

