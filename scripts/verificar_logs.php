<?php
/**
 * Script para verificar onde estão os logs do PHP
 */

echo "<h1>📁 Localização dos Logs do PHP</h1>";
echo "<pre>";

echo "🔍 VERIFICANDO CONFIGURAÇÃO DO PHP:\n";
echo str_repeat("=", 80) . "\n";

// Verificar configuração do PHP
$errorLog = ini_get('error_log');
$logErrors = ini_get('log_errors');
$displayErrors = ini_get('display_errors');

echo "error_log (configurado): " . ($errorLog ?: 'NÃO CONFIGURADO (usa padrão do sistema)') . "\n";
echo "log_errors: " . ($logErrors ? 'ON' : 'OFF') . "\n";
echo "display_errors: " . ($displayErrors ? 'ON' : 'OFF') . "\n";
echo "\n";

// Verificar caminhos comuns no Windows/WAMP
echo "📂 CAMINHOS COMUNS NO WINDOWS/WAMP:\n";
echo str_repeat("-", 80) . "\n";

$commonPaths = [
    'C:\\wamp64\\logs\\php_error.log',
    'C:\\wamp64\\logs\\apache_error.log',
    'C:\\wamp64\\bin\\php\\php8.2.0\\logs\\php_error.log',
    'C:\\wamp64\\bin\\php\\php8.1.0\\logs\\php_error.log',
    'C:\\wamp64\\bin\\php\\php8.0.0\\logs\\php_error.log',
    __DIR__ . '/../logs/php_errors.log',
    __DIR__ . '/../app/logs/php_errors.log',
];

foreach ($commonPaths as $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        $modified = date('Y-m-d H:i:s', filemtime($path));
        echo "✅ ENCONTRADO: {$path}\n";
        echo "   Tamanho: " . number_format($size / 1024, 2) . " KB\n";
        echo "   Última modificação: {$modified}\n";
    } else {
        echo "❌ Não encontrado: {$path}\n";
    }
}

echo "\n";

// Verificar logs do projeto
echo "📂 LOGS DO PROJETO:\n";
echo str_repeat("-", 80) . "\n";

$projectLogs = [
    __DIR__ . '/../app/logs',
    __DIR__ . '/../logs',
];

foreach ($projectLogs as $logDir) {
    if (is_dir($logDir)) {
        echo "✅ Diretório existe: {$logDir}\n";
        $files = glob($logDir . '/*.log');
        if (!empty($files)) {
            echo "   Arquivos encontrados:\n";
            foreach ($files as $file) {
                $size = filesize($file);
                $modified = date('Y-m-d H:i:s', filemtime($file));
                $name = basename($file);
                echo "   - {$name} (" . number_format($size / 1024, 2) . " KB, modificado: {$modified})\n";
            }
        } else {
            echo "   Nenhum arquivo .log encontrado\n";
        }
    } else {
        echo "❌ Diretório não existe: {$logDir}\n";
    }
}

echo "\n";

// Testar onde error_log() está salvando
echo "🧪 TESTE: Onde error_log() está salvando?\n";
echo str_repeat("-", 80) . "\n";

$testMessage = "TESTE DE LOG - " . date('Y-m-d H:i:s') . " - Se você ver esta mensagem, os logs estão funcionando!";
error_log($testMessage);

echo "Mensagem de teste enviada: {$testMessage}\n";
echo "\n";
echo "⚠️ IMPORTANTE: Verifique os arquivos acima para encontrar esta mensagem de teste.\n";
echo "   A mensagem deve aparecer no arquivo onde error_log() está salvando.\n";

echo "\n";

// Verificar se há .user.ini ou php.ini customizado
echo "📄 ARQUIVOS DE CONFIGURAÇÃO:\n";
echo str_repeat("-", 80) . "\n";

$configFiles = [
    __DIR__ . '/.user.ini',
    __DIR__ . '/../.user.ini',
    'C:\\wamp64\\bin\\php\\php8.2.0\\php.ini',
    'C:\\wamp64\\bin\\php\\php8.1.0\\php.ini',
    'C:\\wamp64\\bin\\php\\php8.0.0\\php.ini',
];

foreach ($configFiles as $configFile) {
    if (file_exists($configFile)) {
        echo "✅ Encontrado: {$configFile}\n";
        if (strpos($configFile, '.ini') !== false) {
            $content = file_get_contents($configFile);
            if (preg_match('/error_log\s*=\s*(.+)/i', $content, $matches)) {
                echo "   error_log configurado: " . trim($matches[1]) . "\n";
            }
        }
    }
}

echo "\n";

// Resumo
echo "📋 RESUMO:\n";
echo str_repeat("=", 80) . "\n";
echo "1. Os logs do PHP (error_log()) geralmente estão em:\n";
echo "   - C:\\wamp64\\logs\\php_error.log (WAMP padrão)\n";
echo "   - Ou no caminho configurado em php.ini ou .user.ini\n";
echo "\n";
echo "2. Logs específicos do projeto estão em:\n";
echo "   - app/logs/ (vários arquivos .log)\n";
echo "   - logs/ (alguns logs)\n";
echo "\n";
echo "3. Para ver os logs em tempo real (Windows PowerShell):\n";
echo "   Get-Content C:\\wamp64\\logs\\php_error.log -Wait -Tail 50\n";
echo "\n";
echo "4. Para ver os últimos 100 linhas:\n";
echo "   Get-Content C:\\wamp64\\logs\\php_error.log -Tail 100\n";

echo "</pre>";




