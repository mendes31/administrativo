<?php
/**
 * Script para deletar TODAS as tabelas do banco de dados
 * 
 * ATENÇÃO: Este script vai DELETAR TODAS AS TABELAS e dados!
 * Use apenas se tiver certeza e backup dos dados importantes.
 * 
 * Uso: php scripts/limpar_todas_tabelas.php
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$port = $_ENV['DB_PORT'] ?? 3306;

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== LIMPEZA COMPLETA DO BANCO DE DADOS ===\n\n";
    echo "⚠️  ATENÇÃO: Todas as tabelas serão deletadas!\n\n";
    
    // Desabilitar verificação de foreign keys
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Limpar phinxlog primeiro
    echo "1. Limpando phinxlog...\n";
    $pdo->exec("TRUNCATE TABLE phinxlog");
    echo "   ✅ phinxlog limpo\n\n";
    
    // Buscar todas as tabelas
    echo "2. Buscando todas as tabelas...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "   ℹ️  Nenhuma tabela encontrada\n\n";
    } else {
        echo "   📋 Encontradas " . count($tables) . " tabelas\n\n";
        
        // Deletar cada tabela
        echo "3. Deletando tabelas...\n";
        $deleted = 0;
        foreach ($tables as $table) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
                echo "   ✅ Deletada: {$table}\n";
                $deleted++;
            } catch (PDOException $e) {
                echo "   ❌ Erro ao deletar {$table}: " . $e->getMessage() . "\n";
            }
        }
        echo "\n   ✅ {$deleted} tabela(s) deletada(s)\n\n";
    }
    
    // Reabilitar verificação de foreign keys
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "✅ Limpeza concluída!\n\n";
    echo "Agora você pode executar as migrations do zero:\n";
    echo "php vendor/bin/phinx migrate -c database/phinx.php -e production\n";
    
} catch (PDOException $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

