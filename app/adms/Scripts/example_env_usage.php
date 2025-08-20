<?php
/**
 * Exemplo de uso do Helper EnvLoader
 * 
 * Este script demonstra como usar o EnvLoader em scripts que podem ser executados isoladamente.
 * 
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */

// Carregar o helper EnvLoader
require_once __DIR__ . '/../Helpers/EnvLoader.php';

// Carregar variáveis de ambiente com timezone
if (!\App\adms\Helpers\EnvLoader::loadWithTimezone()) {
    echo "Erro: Não foi possível carregar as configurações do .env\n";
    exit(1);
}

echo "=== Exemplo de Uso do EnvLoader ===\n\n";

// Demonstrar uso das variáveis do .env
echo "Configurações do Banco de Dados:\n";
echo "- Host: " . ($_ENV['DB_HOST'] ?? 'Não configurado') . "\n";
echo "- Database: " . ($_ENV['DB_NAME'] ?? 'Não configurado') . "\n";
echo "- Usuário: " . ($_ENV['DB_USER'] ?? 'Não configurado') . "\n";
echo "- Porta: " . ($_ENV['DB_PORT'] ?? 'Não configurado') . "\n\n";

echo "Configurações da Aplicação:\n";
echo "- Nome: " . ($_ENV['APP_NAME'] ?? 'Não configurado') . "\n";
echo "- Ambiente: " . ($_ENV['APP_ENV'] ?? 'Não configurado') . "\n";
echo "- Timezone: " . ($_ENV['APP_TIMEZONE'] ?? 'Não configurado') . "\n";
echo "- URL: " . ($_ENV['URL_ADM'] ?? 'Não configurado') . "\n\n";

echo "Configurações de E-mail:\n";
echo "- Host SMTP: " . ($_ENV['MAIL_HOST'] ?? 'Não configurado') . "\n";
echo "- Porta SMTP: " . ($_ENV['MAIL_PORT'] ?? 'Não configurado') . "\n";
echo "- Usuário SMTP: " . ($_ENV['MAIL_USERNAME'] ?? 'Não configurado') . "\n\n";

echo "Data/Hora atual: " . date('Y-m-d H:i:s') . "\n";
echo "Timezone atual: " . date_default_timezone_get() . "\n\n";

echo "=== Fim do Exemplo ===\n";

// Exemplo de conexão com banco usando as variáveis do .env
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};port={$_ENV['DB_PORT']}", 
        $_ENV['DB_USER'], 
        $_ENV['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conexão com banco de dados realizada com sucesso!\n";
    
    // Testar uma query simples
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM adms_users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Total de usuários no sistema: " . $result['total'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ Erro na conexão com banco: " . $e->getMessage() . "\n";
}

echo "\nScript executado com sucesso!\n"; 