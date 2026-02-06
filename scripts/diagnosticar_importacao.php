<?php
/**
 * Script para diagnosticar problemas na importação
 * 
 * Uso: php scripts/diagnosticar_importacao.php
 * 
 * Requer: Configuração do banco no .env
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
    
    echo "🔍 === DIAGNÓSTICO DE IMPORTAÇÃO ===\n\n";
    
    // 1. Listar todas as tabelas e contagem de registros
    echo "1. 📊 Contagem de registros por tabela:\n";
    echo str_repeat("-", 60) . "\n";
    echo sprintf("%-40s %15s\n", "Tabela", "Registros");
    echo str_repeat("-", 60) . "\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $totalRegistros = 0;
    foreach ($tabelas as $tabela) {
        try {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$tabela}`");
            $count = $countStmt->fetchColumn();
            $totalRegistros += $count;
            echo sprintf("%-40s %15s\n", $tabela, number_format($count));
        } catch (PDOException $e) {
            echo sprintf("%-40s %15s\n", $tabela, "ERRO: " . $e->getMessage());
        }
    }
    echo str_repeat("-", 60) . "\n";
    echo sprintf("%-40s %15s\n", "TOTAL", number_format($totalRegistros));
    echo "\n";
    
    // 2. Verificar foreign keys quebradas em adms_users
    echo "2. 🔗 Verificando foreign keys quebradas:\n";
    echo str_repeat("-", 60) . "\n";
    
    if (in_array('adms_users', $tabelas)) {
        try {
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as orfaos
                FROM adms_users u
                LEFT JOIN adms_departments d ON u.user_department_id = d.id
                WHERE u.user_department_id IS NOT NULL AND d.id IS NULL
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['orfaos'] > 0) {
                echo "⚠️  Encontrados {$result['orfaos']} usuários com department_id inválido\n";
            } else {
                echo "✅ Nenhum usuário órfão encontrado\n";
            }
        } catch (PDOException $e) {
            echo "ℹ️  Não foi possível verificar (tabela pode não ter foreign key)\n";
        }
    }
    echo "\n";
    
    // 3. Verificar valores NULL em campos NOT NULL
    echo "3. ❌ Verificando valores NULL em campos obrigatórios:\n";
    echo str_repeat("-", 60) . "\n";
    
    $tabelasParaVerificar = ['adms_users', 'adms_access_levels', 'adms_departments'];
    foreach ($tabelasParaVerificar as $tabela) {
        if (!in_array($tabela, $tabelas)) {
            continue;
        }
        
        try {
            // Verificar estrutura
            $stmt = $pdo->query("DESCRIBE `{$tabela}`");
            $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($colunas as $coluna) {
                if ($coluna['Null'] === 'NO' && $coluna['Default'] === null && $coluna['Key'] !== 'PRI') {
                    $colunaName = $coluna['Field'];
                    $stmt = $pdo->query("SELECT COUNT(*) FROM `{$tabela}` WHERE `{$colunaName}` IS NULL");
                    $nullCount = $stmt->fetchColumn();
                    if ($nullCount > 0) {
                        echo "⚠️  {$tabela}.{$colunaName}: {$nullCount} registros com NULL (campo NOT NULL)\n";
                    }
                }
            }
        } catch (PDOException $e) {
            // Ignorar erros
        }
    }
    echo "\n";
    
    // 4. Verificar warnings/erros do MySQL
    echo "4. ⚠️  Verificando warnings do MySQL:\n";
    echo str_repeat("-", 60) . "\n";
    try {
        $stmt = $pdo->query("SHOW WARNINGS");
        $warnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($warnings)) {
            echo "✅ Nenhum warning encontrado\n";
        } else {
            foreach ($warnings as $warning) {
                echo "⚠️  {$warning['Level']}: {$warning['Message']}\n";
            }
        }
    } catch (PDOException $e) {
        echo "ℹ️  Não foi possível verificar warnings\n";
    }
    echo "\n";
    
    // 5. Verificar duplicatas em campos que deveriam ser únicos
    echo "5. 🔍 Verificando possíveis duplicatas:\n";
    echo str_repeat("-", 60) . "\n";
    
    if (in_array('adms_access_levels', $tabelas)) {
        try {
            $stmt = $pdo->query("
                SELECT name, COUNT(*) as duplicatas
                FROM adms_access_levels
                GROUP BY name
                HAVING COUNT(*) > 1
            ");
            $duplicatas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($duplicatas)) {
                echo "✅ Nenhuma duplicata encontrada em adms_access_levels.name\n";
            } else {
                foreach ($duplicatas as $dup) {
                    echo "⚠️  '{$dup['name']}': {$dup['duplicatas']} registros duplicados\n";
                }
            }
        } catch (PDOException $e) {
            echo "ℹ️  Não foi possível verificar duplicatas\n";
        }
    }
    echo "\n";
    
    echo "✅ Diagnóstico concluído!\n";
    echo "\n💡 Dica: Compare os números acima com o que você esperava importar.\n";
    echo "   Se houver diferença, verifique os logs do MySQL ou importe sem INSERT IGNORE para ver os erros.\n";
    
} catch (PDOException $e) {
    echo "❌ ERRO ao conectar ao banco: " . $e->getMessage() . "\n";
    exit(1);
}

