<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

echo "\n🔍 TESTE - CONEXÃO MYSQL LOCAL\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Configuração:\n";
echo "  Host: " . ($_ENV['DB_HOST'] ?? 'não configurado') . "\n";
echo "  Database: " . ($_ENV['DB_NAME'] ?? 'não configurado') . "\n";
echo "  User: " . ($_ENV['DB_USER'] ?? 'não configurado') . "\n";
echo "  Password: " . (isset($_ENV['DB_PASS']) ? '***' : 'não configurado') . "\n\n";

echo "Testando conexão... ";

try {
    $pdo = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    echo "✅ OK\n\n";
    echo "MySQL está funcionando normalmente!\n";
} catch (PDOException $e) {
    echo "❌ ERRO\n\n";
    echo "Mensagem: " . $e->getMessage() . "\n\n";
    echo "⚠️ MySQL local não está funcionando!\n";
    echo "  Verifique se o WAMP/MySQL está rodando.\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";

