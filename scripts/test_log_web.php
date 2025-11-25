<?php
/**
 * Teste de escrita de log quando executado via web
 * Simula o que acontece quando o controller é chamado
 */

// Simular o que o controller faz
$logFile = __DIR__ . '/../app/logs/sap_api.log';
$logFileAlt = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sap_api.log';

echo "=== TESTE DE ESCRITA DE LOG (SIMULANDO WEB) ===\n\n";

echo "Log principal: {$logFile}\n";
echo "Existe diretório: " . (is_dir(dirname($logFile)) ? 'SIM' : 'NÃO') . "\n";
echo "Pode escrever: " . (is_writable(dirname($logFile)) ? 'SIM' : 'NÃO') . "\n\n";

echo "Log alternativo: {$logFileAlt}\n";
echo "Existe diretório: " . (is_dir(dirname($logFileAlt)) ? 'SIM' : 'NÃO') . "\n";
echo "Pode escrever: " . (is_writable(dirname($logFileAlt)) ? 'SIM' : 'NÃO') . "\n\n";

// Tentar escrever em ambos
$message = "[" . date('Y-m-d H:i:s') . "] 🚀 TESTE DE LOG VIA WEB\n";

echo "Tentando escrever no log principal...\n";
$result1 = @file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
echo "Resultado: " . ($result1 !== false ? "✅ SUCESSO ({$result1} bytes)" : "❌ FALHA") . "\n";

if ($result1 === false) {
    $error = error_get_last();
    if ($error) {
        echo "Erro: {$error['message']}\n";
    }
}

echo "\nTentando escrever no log alternativo...\n";
$result2 = @file_put_contents($logFileAlt, $message, FILE_APPEND | LOCK_EX);
echo "Resultado: " . ($result2 !== false ? "✅ SUCESSO ({$result2} bytes)" : "❌ FALHA") . "\n";

if ($result2 === false) {
    $error = error_get_last();
    if ($error) {
        echo "Erro: {$error['message']}\n";
    }
}

// Verificar conteúdo
echo "\n=== CONTEÚDO DOS LOGS ===\n";
if (file_exists($logFile)) {
    echo "\nLog principal (últimas 10 linhas):\n";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -10);
    echo implode("", $lastLines);
} else {
    echo "\n❌ Log principal não existe!\n";
}

if (file_exists($logFileAlt)) {
    echo "\nLog alternativo (últimas 10 linhas):\n";
    $lines = file($logFileAlt);
    $lastLines = array_slice($lines, -10);
    echo implode("", $lastLines);
} else {
    echo "\n❌ Log alternativo não existe!\n";
}

