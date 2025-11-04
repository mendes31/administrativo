<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

use App\adms\Models\Services\SapB1HanaConnection;

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  🔷 TESTE DE CONEXÃO SAP BUSINESS ONE HANA\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "📋 CONFIGURAÇÃO:\n";
echo "───────────────────────────────────────────────────────────────\n";
$config = SapB1HanaConnection::getConfig();
foreach ($config as $key => $value) {
    printf("  %-15s: %s\n", ucfirst($key), $value);
}
echo "\n";

echo "🔌 TESTANDO CONEXÃO...\n";
echo "───────────────────────────────────────────────────────────────\n";
$result = SapB1HanaConnection::testConnection();

if ($result['success']) {
    echo "✅ CONEXÃO ESTABELECIDA COM SUCESSO!\n\n";
    echo "📊 INFORMAÇÕES DO SERVIDOR:\n";
    echo "───────────────────────────────────────────────────────────────\n";
    printf("  Database     : %s\n", $result['database']);
    printf("  Schema       : %s\n", $result['schema']);
    printf("  User         : %s\n", $result['user']);
    echo "\n";
    
    echo "📝 TESTANDO CONSULTAS...\n";
    echo "───────────────────────────────────────────────────────────────\n";
    
    try {
        $totalClientes = SapB1HanaConnection::querySingle("SELECT COUNT(*) FROM OCRD WHERE CardType = 'C'");
        echo sprintf("  ✅ Total de Clientes         : %s\n", number_format($totalClientes, 0, ',', '.'));
        
        $totalFornecedores = SapB1HanaConnection::querySingle("SELECT COUNT(*) FROM OCRD WHERE CardType = 'S'");
        echo sprintf("  ✅ Total de Fornecedores     : %s\n", number_format($totalFornecedores, 0, ',', '.'));
        
        $totalItens = SapB1HanaConnection::querySingle("SELECT COUNT(*) FROM OITM");
        echo sprintf("  ✅ Total de Itens            : %s\n", number_format($totalItens, 0, ',', '.'));
        
        echo "\n👥 PRIMEIROS 5 CLIENTES:\n";
        echo "───────────────────────────────────────────────────────────────\n";
        $clientes = SapB1HanaConnection::query("SELECT CardCode, CardName, Phone1, E_Mail FROM OCRD WHERE CardType = 'C' ORDER BY CardName LIMIT 5");
        
        if (empty($clientes)) {
            echo "  ⚠️  Nenhum cliente encontrado\n";
        } else {
            foreach ($clientes as $cliente) {
                echo sprintf("  📌 %-10s: %s\n", $cliente['CardCode'], $cliente['CardName']);
                if (!empty($cliente['Phone1'])) echo sprintf("     📞 %s\n", $cliente['Phone1']);
                if (!empty($cliente['E_Mail'])) echo sprintf("     📧 %s\n", $cliente['E_Mail']);
                echo "\n";
            }
        }
    } catch (Exception $e) {
        echo "  ❌ Erro ao executar consultas: " . $e->getMessage() . "\n\n";
    }
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  ✅ TESTE CONCLUÍDO COM SUCESSO!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
} else {
    echo "❌ FALHA NA CONEXÃO!\n\n";
    echo "📋 DETALHES DO ERRO:\n";
    echo "───────────────────────────────────────────────────────────────\n";
    echo wordwrap($result['message'], 60) . "\n\n";
    
    echo "💡 DICAS DE TROUBLESHOOTING:\n";
    echo "───────────────────────────────────────────────────────────────\n";
    echo "  1. Verifique as credenciais no arquivo .env\n";
    echo "  2. Confirme que o servidor HANA está acessível\n";
    echo "  3. Teste conectividade: telnet {$config['host']} {$config['port']}\n";
    echo "  4. Verifique se o driver HDBODBC está instalado\n\n";
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  ❌ TESTE FALHOU\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    exit(1);
}

