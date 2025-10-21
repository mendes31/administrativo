<?php
/**
 * Script de Teste de Performance - Captura de Hostname
 * 
 * Este script testa a performance da captura de hostname
 * com diferentes tipos de IPs para validar a implementação.
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\adms\Controllers\Services\RequestHelper;
use App\adms\Helpers\EnvLoader;

// Carregar variáveis de ambiente
EnvLoader::load();

echo "=================================================\n";
echo "  TESTE DE PERFORMANCE - CAPTURA DE HOSTNAME\n";
echo "=================================================\n\n";

// Simular diferentes cenários de IPs
$testCases = [
    [
        'description' => 'IP Local (localhost)',
        'ip' => '127.0.0.1',
    ],
    [
        'description' => 'IP Privado (rede interna)',
        'ip' => '192.168.1.1',
    ],
    [
        'description' => 'IP Público (Google DNS)',
        'ip' => '8.8.8.8',
    ],
    [
        'description' => 'IP Público (Cloudflare DNS)',
        'ip' => '1.1.1.1',
    ],
    [
        'description' => 'IP Inválido',
        'ip' => '0.0.0.0',
    ],
];

echo "1. TESTE DE MÉTODOS DO RequestHelper\n";
echo "=====================================\n\n";

// Testar método getClientIp
echo "Testando getClientIp():\n";
$clientIp = RequestHelper::getClientIp();
echo "  IP detectado: " . $clientIp . "\n\n";

// Testar método getClientHostname
echo "Testando getClientHostname():\n";
$startTime = microtime(true);
$hostname = RequestHelper::getClientHostname();
$endTime = microtime(true);
$executionTime = ($endTime - $startTime) * 1000;

echo "  Hostname detectado: " . $hostname . "\n";
echo "  Tempo de execução: " . number_format($executionTime, 2) . " ms\n\n";

// Testar método getUserAgent
echo "Testando getUserAgent():\n";
$userAgent = RequestHelper::getUserAgent();
echo "  User Agent: " . ($userAgent ?? 'N/A') . "\n\n";

// Testar método getClientInfo
echo "Testando getClientInfo():\n";
$startTime = microtime(true);
$clientInfo = RequestHelper::getClientInfo();
$endTime = microtime(true);
$executionTime = ($endTime - $startTime) * 1000;

echo "  Informações completas do cliente:\n";
foreach ($clientInfo as $key => $value) {
    $displayValue = is_null($value) ? 'null' : $value;
    echo "    - {$key}: {$displayValue}\n";
}
echo "  Tempo de execução: " . number_format($executionTime, 2) . " ms\n\n";

echo "\n2. TESTE DE RESOLUÇÃO DE HOSTNAME COM DIFERENTES IPs\n";
echo "=====================================================\n\n";

foreach ($testCases as $index => $testCase) {
    echo ($index + 1) . ". " . $testCase['description'] . "\n";
    echo "   IP: " . $testCase['ip'] . "\n";
    
    // Simular o IP no $_SERVER para teste
    $_SERVER['REMOTE_ADDR'] = $testCase['ip'];
    
    // Medir tempo de execução
    $startTime = microtime(true);
    $hostname = RequestHelper::getClientHostname();
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000;
    
    echo "   Hostname: " . $hostname . "\n";
    echo "   Tempo: " . number_format($executionTime, 2) . " ms\n";
    
    // Avaliar performance
    if ($executionTime < 100) {
        echo "   Status: ✓ Excelente (< 100ms)\n";
    } elseif ($executionTime < 500) {
        echo "   Status: ⚠ Aceitável (100-500ms)\n";
    } elseif ($executionTime < 2000) {
        echo "   Status: ⚠ Lento (500ms-2s)\n";
    } else {
        echo "   Status: ✗ Muito Lento (> 2s)\n";
    }
    
    echo "\n";
}

echo "\n3. TESTE DE CAPTURA USANDO gethostbyaddr() DIRETO\n";
echo "==================================================\n\n";

foreach ($testCases as $index => $testCase) {
    echo ($index + 1) . ". " . $testCase['description'] . "\n";
    echo "   IP: " . $testCase['ip'] . "\n";
    
    // Teste direto com gethostbyaddr
    $startTime = microtime(true);
    $hostname = @gethostbyaddr($testCase['ip']);
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000;
    
    $result = ($hostname !== $testCase['ip'] && $hostname !== false) ? $hostname : 'Não resolvido';
    
    echo "   Resultado: " . $result . "\n";
    echo "   Tempo: " . number_format($executionTime, 2) . " ms\n\n";
}

echo "\n4. RECOMENDAÇÕES DE PERFORMANCE\n";
echo "================================\n\n";

echo "Com base nos testes realizados:\n\n";

$avgTime = 0;
$testCount = 0;

foreach ($testCases as $testCase) {
    $_SERVER['REMOTE_ADDR'] = $testCase['ip'];
    $startTime = microtime(true);
    RequestHelper::getClientHostname();
    $endTime = microtime(true);
    $avgTime += ($endTime - $startTime) * 1000;
    $testCount++;
}

$avgTime = $avgTime / $testCount;

echo "Tempo médio de execução: " . number_format($avgTime, 2) . " ms\n\n";

if ($avgTime < 100) {
    echo "✓ Performance EXCELENTE\n";
    echo "  - A captura de hostname está funcionando de forma otimizada\n";
    echo "  - Não há necessidade de implementar cache\n";
} elseif ($avgTime < 500) {
    echo "⚠ Performance ACEITÁVEL\n";
    echo "  - A captura de hostname está funcionando adequadamente\n";
    echo "  - Considerar implementar cache para IPs frequentes\n";
} else {
    echo "✗ Performance RUIM\n";
    echo "  - Recomenda-se FORTEMENTE implementar cache\n";
    echo "  - Considerar implementar timeout mais agressivo\n";
    echo "  - Avaliar executar resolução em background\n";
}

echo "\n\n5. CONFIGURAÇÕES RECOMENDADAS\n";
echo "==============================\n\n";

echo "PHP.ini sugerido:\n";
echo "  default_socket_timeout = 2  ; Timeout de 2 segundos para DNS\n\n";

echo "Implementação de Cache (exemplo):\n";
echo "  - Usar APCu, Redis ou Memcached\n";
echo "  - TTL sugerido: 3600 segundos (1 hora)\n";
echo "  - Chave: 'hostname_' . md5(\$ip)\n\n";

echo "\n=================================================\n";
echo "  FIM DO TESTE\n";
echo "=================================================\n";

