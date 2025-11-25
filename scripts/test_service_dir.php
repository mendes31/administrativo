<?php
/**
 * Teste do __DIR__ no contexto do serviço
 */

require_once __DIR__ . '/../app/adms/Models/Services/SapReportApiService.php';

use App\adms\Models\Services\SapReportApiService;

// Usar Reflection para acessar propriedades privadas ou métodos
$reflection = new ReflectionClass('App\adms\Models\Services\SapReportApiService');
$filename = $reflection->getFileName();

echo "=== ANÁLISE DO CAMINHO ===\n\n";
echo "Arquivo do serviço: {$filename}\n";
echo "__DIR__ do serviço seria: " . dirname($filename) . "\n\n";

// Calcular caminho do log
$serviceDir = dirname($filename);
$logDir = $serviceDir . '/../../../logs';
$logFile = $logDir . '/sap_api.log';

echo "Caminho calculado do log: {$logFile}\n";
echo "Resolvido (realpath): " . (realpath($logDir) ?: 'NÃO RESOLVIDO') . "\n\n";

// Tentar escrever
$message = "[" . date('Y-m-d H:i:s') . "] Teste do serviço\n";
$result = @file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
echo "Tentativa de escrita: " . ($result !== false ? "✅ SUCESSO ({$result} bytes)" : "❌ FALHA") . "\n";

// Verificar se existe
echo "\nArquivo existe: " . (file_exists($logFile) ? 'SIM' : 'NÃO') . "\n";
if (file_exists($logFile)) {
    echo "Tamanho: " . filesize($logFile) . " bytes\n";
}

