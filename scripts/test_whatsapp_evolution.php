<?php
/**
 * Diagnóstico da integração Evolution API + WhatsApp.
 *
 * Uso: php scripts/test_whatsapp_evolution.php
 */
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

use App\adms\Helpers\SendWhatsAppService;
use App\adms\Models\Repository\AdmsWhatsAppConfigRepository;

echo "\n=== Diagnóstico WhatsApp / Evolution API ===\n\n";

$repo = new AdmsWhatsAppConfigRepository();
$config = $repo->getConfig();

if (empty($config) || empty($config['is_active'])) {
    echo "❌ WhatsApp não configurado ou inativo no banco.\n";
    exit(1);
}

$baseUrl = rtrim((string)$config['api_url'], '/');
$apiKey  = (string)($config['api_key'] ?? '');
$instance = (string)($config['instance_name'] ?? '');

echo "URL base: {$baseUrl}\n";
echo "Instância: {$instance}\n";
echo "API Key: " . (strlen($apiKey) > 0 ? substr($apiKey, 0, 4) . '***' : '(vazia)') . "\n\n";

// 1) API online (GET raiz)
echo "1) API online (GET /)\n";
$ch = curl_init($baseUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $apiKey,
        'ngrok-skip-browser-warning: true',
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 15,
]);
$body = curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   HTTP {$code}\n";
if ($body) {
    $json = json_decode($body, true);
    if (is_array($json) && isset($json['message'])) {
        echo "   → {$json['message']} (v{$json['version']})\n";
    }
}
echo "\n";

// 2) Listar instâncias (nome exato para o cadastro)
echo "2) Instâncias na Evolution API\n";
$ch = curl_init($baseUrl . '/instance/fetchInstances');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['apikey: ' . $apiKey, 'ngrok-skip-browser-warning: true'],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 15,
]);
$instBody = curl_exec($ch);
$instCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($instCode >= 200 && $instCode < 300 && $instBody) {
    $instances = json_decode($instBody, true);
    $list = is_array($instances) ? $instances : [];
    foreach ($list as $row) {
        $name = $row['name'] ?? $row['instanceName'] ?? $row['instance']['instanceName'] ?? '?';
        $st = $row['connectionStatus'] ?? $row['state'] ?? $row['instance']['state'] ?? '?';
        $match = (strcasecmp((string)$name, $instance) === 0) ? ' ← cadastrado no sistema' : '';
        echo "   - {$name} (status: {$st}){$match}\n";
    }
} else {
    echo "   ⚠ Não foi possível listar (HTTP {$instCode})\n";
}
echo "\n";

// 3) Estado da instância (endpoint connectionState)
echo "3) Estado via /instance/connectionState\n";
$state = SendWhatsAppService::getEvolutionConnectionState($config);
if (!($state['success'] ?? false)) {
    echo "   ❌ " . ($state['error'] ?? 'Erro desconhecido') . "\n";
} else {
    $st = $state['state'] ?? 'unknown';
    $icon = ($st === 'open') ? '✅' : '⚠';
    echo "   {$icon} state = {$st}\n";
    if (!empty($state['raw'])) {
        echo "   JSON: " . json_encode($state['raw'], JSON_UNESCAPED_UNICODE) . "\n";
    }
    if ($st !== 'open') {
        echo "   → Manager pode mostrar 'conectado' com socket fechado; desconecte/reconecte.\n";
    }
}
echo "\n";

// 4) Envio de teste (opcional via argumento)
$testNumber = $argv[1] ?? '';
if ($testNumber !== '') {
    echo "4) Envio de teste para {$testNumber}\n";
    $result = SendWhatsAppService::sendMessage($testNumber, 'Teste diagnóstico Evolution API - ' . date('d/m/Y H:i:s'));
    if ($result['success'] ?? false) {
        echo "   ✅ Enviado! message_id: " . ($result['message_id'] ?? '-') . "\n";
    } else {
        echo "   ❌ " . ($result['error'] ?? 'Erro') . "\n";
    }
} else {
    echo "4) Envio de teste omitido. Para testar: php scripts/test_whatsapp_evolution.php 55996621351\n";
}

echo "\n=== Fim ===\n\n";
