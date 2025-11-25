<?php
/**
 * Teste direto de escrita no log
 */

$logDir = __DIR__ . '/../app/logs';
$logFile = $logDir . '/sap_api.log';

echo "=== TESTE DIRETO DE LOG ===\n\n";
echo "Diretório: {$logDir}\n";
echo "Existe: " . (is_dir($logDir) ? 'SIM' : 'NÃO') . "\n";
echo "Arquivo: {$logFile}\n";
echo "Existe: " . (file_exists($logFile) ? 'SIM' : 'NÃO') . "\n\n";

// Garantir diretório
if (!is_dir($logDir)) {
    echo "Criando diretório...\n";
    $result = mkdir($logDir, 0755, true);
    echo "Resultado: " . ($result ? 'SUCESSO' : 'FALHA') . "\n";
}

// Tentar escrever
$message = "[" . date('Y-m-d H:i:s') . "] Teste direto de escrita\n";
echo "\nTentando escrever: {$message}\n";

$result = file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
if ($result !== false) {
    echo "✅ Escrita bem-sucedida! Bytes: {$result}\n";
} else {
    echo "❌ Falha na escrita!\n";
    $error = error_get_last();
    if ($error) {
        echo "Erro: {$error['message']}\n";
        echo "Arquivo: {$error['file']}\n";
        echo "Linha: {$error['line']}\n";
    }
}

// Verificar conteúdo
echo "\n=== CONTEÚDO DO ARQUIVO ===\n";
if (file_exists($logFile)) {
    echo file_get_contents($logFile);
} else {
    echo "Arquivo não existe!\n";
}

