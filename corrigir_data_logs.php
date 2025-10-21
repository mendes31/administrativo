<?php
/**
 * Script para Corrigir Data dos Logs
 * 
 * Este script corrige logs com data 2025 para 2024
 * ATENÇÃO: Execute apenas após corrigir a data do servidor!
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\adms\Helpers\EnvLoader;

// Carregar variáveis de ambiente
EnvLoader::load();

echo "=================================================\n";
echo "  CORREÇÃO DE DATA DOS LOGS\n";
echo "=================================================\n\n";

// Verificar se a data do servidor está correta
$currentYear = (int)date('Y');
if ($currentYear !== 2024) {
    echo "❌ ERRO: Data do servidor ainda está incorreta!\n";
    echo "📅 Ano atual: {$currentYear}\n";
    echo "📅 Ano esperado: 2024\n";
    echo "🔧 AÇÃO NECESSÁRIA: Corrija a data do servidor primeiro!\n";
    echo "   Windows: Painel de Controle → Data e Hora\n";
    echo "   Linux: sudo date -s \"2024-10-21 18:00:00\"\n";
    exit(1);
}

echo "✅ Data do servidor está correta: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Conectar ao banco de dados
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ Conectado ao banco de dados\n\n";
    
    // Verificar logs com data 2025
    $sql = "SELECT COUNT(*) as total_2025 FROM adms_log_alteracoes WHERE YEAR(data_alteracao) = 2025";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    
    $total2025 = $result['total_2025'];
    
    echo "📊 ESTATÍSTICAS:\n";
    echo "   Logs com data 2025: {$total2025}\n";
    
    if ($total2025 === 0) {
        echo "✅ Nenhum log com data 2025 encontrado!\n";
        echo "🎉 Não é necessário corrigir nada.\n";
        exit(0);
    }
    
    echo "\n⚠️  ATENÇÃO: {$total2025} logs serão corrigidos!\n";
    echo "📅 Data será alterada de 2025 para 2024\n";
    echo "🔄 Processo irá subtrair 1 ano de todas as datas\n\n";
    
    // Confirmar execução
    echo "❓ Deseja continuar? (digite 'SIM' para confirmar): ";
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if (strtoupper($confirmation) !== 'SIM') {
        echo "❌ Operação cancelada pelo usuário.\n";
        exit(0);
    }
    
    echo "\n🔄 Iniciando correção...\n\n";
    
    // Corrigir logs de alterações
    $sql = "UPDATE adms_log_alteracoes 
            SET data_alteracao = DATE_SUB(data_alteracao, INTERVAL 1 YEAR) 
            WHERE YEAR(data_alteracao) = 2025";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $affectedAlteracoes = $stmt->rowCount();
    
    echo "✅ Logs de alterações corrigidos: {$affectedAlteracoes}\n";
    
    // Corrigir logs de acesso
    $sql = "UPDATE adms_log_acessos 
            SET data_acesso = DATE_SUB(data_acesso, INTERVAL 1 YEAR) 
            WHERE YEAR(data_acesso) = 2025";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $affectedAcessos = $stmt->rowCount();
    
    echo "✅ Logs de acesso corrigidos: {$affectedAcessos}\n";
    
    // Verificar se ainda há logs com data 2025
    $sql = "SELECT COUNT(*) as total_2025 FROM adms_log_alteracoes WHERE YEAR(data_alteracao) = 2025";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    
    $remaining2025 = $result['total_2025'];
    
    if ($remaining2025 === 0) {
        echo "\n🎉 CORREÇÃO CONCLUÍDA COM SUCESSO!\n";
        echo "✅ Todos os logs foram corrigidos\n";
        echo "📅 Datas agora estão em 2024\n";
    } else {
        echo "\n⚠️  ATENÇÃO: Ainda há {$remaining2025} logs com data 2025\n";
        echo "🔍 Verifique se há outras tabelas com logs\n";
    }
    
    // Mostrar estatísticas finais
    echo "\n📊 ESTATÍSTICAS FINAIS:\n";
    echo "   Logs de alterações corrigidos: {$affectedAlteracoes}\n";
    echo "   Logs de acesso corrigidos: {$affectedAcessos}\n";
    echo "   Total de logs corrigidos: " . ($affectedAlteracoes + $affectedAcessos) . "\n";
    
    // Verificar logs mais recentes
    $sql = "SELECT data_alteracao, COUNT(*) as total 
            FROM adms_log_alteracoes 
            WHERE data_alteracao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(data_alteracao) 
            ORDER BY data_alteracao DESC 
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $recentLogs = $stmt->fetchAll();
    
    echo "\n📅 LOGS RECENTES (últimos 7 dias):\n";
    foreach ($recentLogs as $log) {
        echo "   " . $log['data_alteracao'] . " - {$log['total']} logs\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "🔧 Verifique as configurações de banco de dados\n";
    exit(1);
}

echo "\n=================================================\n";
echo "  CORREÇÃO CONCLUÍDA\n";
echo "=================================================\n";
