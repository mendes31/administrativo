<?php
/**
 * Teste do caminho do log
 */

// Simular o caminho usado no SapReportApiService
$serviceFile = __DIR__ . '/../app/adms/Models/Services/SapReportApiService.php';
echo "Arquivo do serviço: {$serviceFile}\n";
echo "Existe: " . (file_exists($serviceFile) ? 'SIM' : 'NÃO') . "\n\n";

// Calcular o caminho do log
$serviceDir = __DIR__ . '/../app/adms/Models/Services';
$logFile = $serviceDir . '/../../logs/sap_api.log';
$logFileAbsolute = realpath(__DIR__ . '/../app/adms/Models/Services') . '/../../logs/sap_api.log';

echo "Caminho relativo (do serviço): {$logFile}\n";
echo "Caminho absoluto: {$logFileAbsolute}\n\n";

// Tentar resolver
$resolved = realpath(dirname($logFile));
echo "Diretório resolvido: " . ($resolved ?: 'NÃO RESOLVIDO') . "\n";

// Testar caminho absoluto a partir da raiz
$rootLogFile = __DIR__ . '/../app/logs/sap_api.log';
echo "\nCaminho a partir da raiz: {$rootLogFile}\n";
echo "Existe diretório: " . (is_dir(dirname($rootLogFile)) ? 'SIM' : 'NÃO') . "\n";

// Tentar escrever
echo "\n=== TESTE DE ESCRITA ===\n";
$testMessage = "Teste de log em " . date('Y-m-d H:i:s') . "\n";
$result = @file_put_contents($rootLogFile, $testMessage, FILE_APPEND);
if ($result !== false) {
    echo "✅ Escrita bem-sucedida! Bytes escritos: {$result}\n";
    echo "Conteúdo do arquivo:\n";
    echo file_get_contents($rootLogFile);
} else {
    echo "❌ Falha na escrita!\n";
    $error = error_get_last();
    echo "Erro: " . ($error['message'] ?? 'Desconhecido') . "\n";
}

