<?php
/**
 * Script para remover tabelas CRM criadas incorretamente
 * Execute: php scripts/drop_crm_tables.php
 */

require __DIR__ . '/../vendor/autoload.php';

// Carregar .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $pdo = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    
    echo "Conectado ao banco: " . $_ENV['DB_NAME'] . "\n\n";
    
    // Desabilitar foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    echo "Foreign key checks desabilitados\n\n";
    
    // Tabelas na ordem de dependência (inversa)
    $tables = [
        'crm_partner_tags',
        'crm_documents',
        'crm_notes',
        'crm_stage_history',
        'crm_activities',
        'crm_opportunities',
        'crm_pipeline_stages',
        'crm_tags',
        'crm_partners'
    ];
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "✓ Tabela $table removida\n";
        } catch (Exception $e) {
            echo "✗ Erro ao remover $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Reabilitar foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "\nForeign key checks reabilitados\n\n";
    
    echo "════════════════════════════════════════\n";
    echo "✅ PRONTO! Tabelas CRM removidas.\n";
    echo "════════════════════════════════════════\n\n";
    echo "Agora execute as migrations:\n";
    echo "cd database\n";
    echo "php ..\\vendor\\bin\\phinx migrate\n\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

