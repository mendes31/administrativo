<?php
/**
 * Script de Debug para Login
 * Execute este arquivo para verificar possíveis problemas no processo de login
 */

require './vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

echo "<h2>Debug do Sistema de Login</h2>";

// 1. Verificar configuração da sessão
echo "<h3>1. Configuração da Sessão</h3>";
echo "<p>session.save_handler: " . ini_get('session.save_handler') . "</p>";
echo "<p>session.save_path: " . ini_get('session.save_path') . "</p>";
echo "<p>session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "</p>";
echo "<p>session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "</p>";
echo "<p>session.use_strict_mode: " . ini_get('session.use_strict_mode') . "</p>";

// 2. Verificar se a página Login está configurada no banco
echo "<h3>2. Verificação da Página Login no Banco</h3>";
try {
    $pdo = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'], 
        $_ENV['DB_USER'], 
        $_ENV['DB_PASS']
    );
    
    $stmt = $pdo->query("SELECT * FROM adms_pages WHERE controller = 'Login'");
    $loginPage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($loginPage) {
        echo "<p>✅ Página Login encontrada:</p>";
        echo "<ul>";
        echo "<li>ID: {$loginPage['id']}</li>";
        echo "<li>Nome: {$loginPage['name']}</li>";
        echo "<li>Controller: {$loginPage['controller']}</li>";
        echo "<li>URL: {$loginPage['controller_url']}</li>";
        echo "<li>Diretório: {$loginPage['directory']}</li>";
        echo "<li>Página Pública: " . ($loginPage['public_page'] ? 'SIM' : 'NÃO') . "</li>";
        echo "<li>Status: " . ($loginPage['page_status'] ? 'ATIVA' : 'INATIVA') . "</li>";
        echo "<li>Pacote: {$loginPage['adms_packages_page_id']}</li>";
        echo "<li>Grupo: {$loginPage['adms_groups_page_id']}</li>";
        echo "</ul>";
    } else {
        echo "<p>❌ Página Login NÃO encontrada no banco!</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro ao conectar com banco: " . $e->getMessage() . "</p>";
}

// 3. Verificar arquivos de sessão
echo "<h3>3. Arquivos de Sessão</h3>";
$sessionPath = ini_get('session.save_path') ?: '/tmp';
if (is_dir($sessionPath)) {
    $files = glob($sessionPath . '/sess_*');
    echo "<p>Arquivos de sessão encontrados: " . count($files) . "</p>";
    if (count($files) > 0) {
        echo "<p>Últimos 5 arquivos:</p><ul>";
        $recentFiles = array_slice($files, -5);
        foreach ($recentFiles as $file) {
            $fileTime = filemtime($file);
            $fileSize = filesize($file);
            echo "<li>" . basename($file) . " - " . date('Y-m-d H:i:s', $fileTime) . " - " . $fileSize . " bytes</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p>❌ Diretório de sessão não acessível: {$sessionPath}</p>";
}

// 4. Verificar variáveis de ambiente
echo "<h3>4. Variáveis de Ambiente</h3>";
echo "<p>URL_ADM: " . ($_ENV['URL_ADM'] ?? 'NÃO DEFINIDA') . "</p>";
echo "<p>APP_NAME: " . ($_ENV['APP_NAME'] ?? 'NÃO DEFINIDA') . "</p>";
echo "<p>DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NÃO DEFINIDA') . "</p>";

// 5. Verificar permissões de arquivos
echo "<h3>5. Permissões de Arquivos</h3>";
$filesToCheck = [
    './app/adms/Views/login/login.php',
    './app/adms/Controllers/login/Login.php',
    './app/adms/Views/layouts/login.php',
    './app/adms/Views/layouts/main.php'
];

foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $readable = is_readable($file);
        $writable = is_writable($file);
        echo "<p>✅ {$file}: " . substr(sprintf('%o', $perms), -4) . " - R: " . ($readable ? 'SIM' : 'NÃO') . " W: " . ($writable ? 'SIM' : 'NÃO') . "</p>";
    } else {
        echo "<p>❌ {$file}: ARQUIVO NÃO EXISTE</p>";
    }
}

// 6. Verificar logs de erro
echo "<h3>6. Logs de Erro</h3>";
$logFiles = [
    './logs/session_debug.log',
    './logs/login_debug.log',
    './logs/session_debug2.log'
];

foreach ($logFiles as $logFile) {
    if (file_exists($logFile)) {
        $size = filesize($logFile);
        $lastModified = filemtime($logFile);
        echo "<p>📄 {$logFile}: " . number_format($size) . " bytes - Última modificação: " . date('Y-m-d H:i:s', $lastModified) . "</p>";
        
        if ($size > 0) {
            $lastLines = file($logFile);
            $lastLines = array_slice($lastLines, -3); // Últimas 3 linhas
            echo "<p>Últimas linhas:</p><pre>";
            foreach ($lastLines as $line) {
                echo htmlspecialchars($line);
            }
            echo "</pre>";
        }
    } else {
        echo "<p>❌ {$logFile}: ARQUIVO NÃO EXISTE</p>";
    }
}

// 7. Teste de sessão
echo "<h3>7. Teste de Sessão</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>Status da sessão: " . session_status() . "</p>";
echo "<p>ID da sessão: " . (session_id() ?: 'NENHUM') . "</p>";
echo "<p>Nome da sessão: " . session_name() . "</p>";

// 8. Verificar se há conflitos de roteamento
echo "<h3>8. Verificação de Roteamento</h3>";
echo "<p>REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NÃO DEFINIDA') . "</p>";
echo "<p>REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NÃO DEFINIDA') . "</p>";
echo "<p>QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'NÃO DEFINIDA') . "</p>";

echo "<hr>";
echo "<p><strong>Para testar o login:</strong></p>";
echo "<p>1. Acesse: <a href='{$_ENV['URL_ADM']}login' target='_blank'>Página de Login</a></p>";
echo "<p>2. Tente fazer login e observe se o problema persiste</p>";
echo "<p>3. Verifique o console do navegador para erros JavaScript</p>";
echo "<p>4. Verifique os logs em ./logs/ para mensagens de erro</p>";
?>
