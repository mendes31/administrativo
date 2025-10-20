<?php
// Script para adicionar campos CPF e Celular diretamente no banco
require_once 'vendor/autoload.php';

// Carregar variáveis de ambiente
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

use App\adms\Models\Services\DbConnection;

try {
    $db = new DbConnection();
    $connection = $db->getConnection();
    
    echo "=== CORREÇÃO DE CAMPOS CPF E CELULAR ===\n";
    
    // Verificar se as colunas já existem
    $stmt = $connection->query("SHOW COLUMNS FROM adms_users LIKE 'cpf'");
    $cpfExists = $stmt->fetch();
    
    $stmt = $connection->query("SHOW COLUMNS FROM adms_users LIKE 'celular'");
    $celularExists = $stmt->fetch();
    
    echo "CPF existe: " . ($cpfExists ? 'SIM' : 'NÃO') . "\n";
    echo "Celular existe: " . ($celularExists ? 'SIM' : 'NÃO') . "\n";
    
    // Adicionar coluna CPF se não existir
    if (!$cpfExists) {
        echo "\nAdicionando coluna CPF...\n";
        $connection->exec("ALTER TABLE adms_users ADD COLUMN cpf VARCHAR(14) NULL COMMENT 'CPF do usuário no formato 000.000.000-00' AFTER username");
        echo "✅ Coluna CPF adicionada!\n";
    } else {
        echo "✅ Coluna CPF já existe!\n";
    }
    
    // Adicionar coluna Celular se não existir
    if (!$celularExists) {
        echo "\nAdicionando coluna Celular...\n";
        $connection->exec("ALTER TABLE adms_users ADD COLUMN celular VARCHAR(20) NULL COMMENT 'Celular do usuário, ex: (00) 00000-0000' AFTER cpf");
        echo "✅ Coluna Celular adicionada!\n";
    } else {
        echo "✅ Coluna Celular já existe!\n";
    }
    
    // Verificar se o índice único existe
    $stmt = $connection->query("SHOW INDEX FROM adms_users WHERE Key_name = 'idx_adms_users_cpf_unique'");
    $indexExists = $stmt->fetch();
    
    if (!$indexExists) {
        echo "\nAdicionando índice único para CPF...\n";
        $connection->exec("ALTER TABLE adms_users ADD UNIQUE INDEX idx_adms_users_cpf_unique (cpf)");
        echo "✅ Índice único adicionado!\n";
    } else {
        echo "✅ Índice único já existe!\n";
    }
    
    // Verificar estrutura final
    echo "\n=== ESTRUTURA FINAL ===\n";
    $stmt = $connection->query("DESCRIBE adms_users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if (in_array($column['Field'], ['cpf', 'celular'])) {
            echo "✅ {$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Comment']}\n";
        }
    }
    
    // Testar consulta
    echo "\n=== TESTE DE CONSULTA ===\n";
    $stmt = $connection->query("SELECT id, name, email, cpf, celular FROM adms_users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Consulta funcionou!\n";
        echo "Usuário: {$user['name']}\n";
        echo "CPF: " . ($user['cpf'] ?? 'NULL') . "\n";
        echo "Celular: " . ($user['celular'] ?? 'NULL') . "\n";
    } else {
        echo "❌ Consulta falhou!\n";
    }
    
    echo "\n🎉 Correção concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
