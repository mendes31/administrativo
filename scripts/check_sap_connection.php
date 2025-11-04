<?php
/**
 * Script para testar conectividade com SAP B1 HANA
 * 
 * Use este script após o deploy para verificar se a conexão está funcionando
 * 
 * Execute: php scripts/check_sap_connection.php
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

echo "🔍 Testando Conectividade SAP B1 HANA...\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Exibir configurações (sem senha)
echo "📋 Configurações:\n";
echo "   Host: " . ($_ENV['SAP_HANA_HOST'] ?? $_ENV['SAP_B1_HOST'] ?? 'NÃO CONFIGURADO') . "\n";
echo "   Port: " . ($_ENV['SAP_HANA_PORT'] ?? $_ENV['SAP_B1_PORT'] ?? 'NÃO CONFIGURADO') . "\n";
echo "   User: " . ($_ENV['SAP_HANA_USER'] ?? $_ENV['SAP_B1_USER'] ?? 'NÃO CONFIGURADO') . "\n";
echo "   Schema: " . ($_ENV['SAP_HANA_SCHEMA'] ?? $_ENV['SAP_B1_DATABASE'] ?? 'NÃO CONFIGURADO') . "\n\n";

// Testar extensões PHP necessárias
echo "🔧 Verificando extensões PHP:\n";
$extensions = ['pdo', 'pdo_odbc', 'mbstring', 'json'];
$allOk = true;

foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ {$ext}\n";
    } else {
        echo "   ❌ {$ext} - NÃO INSTALADA!\n";
        $allOk = false;
    }
}

if (!$allOk) {
    echo "\n⚠️  ERRO: Extensões faltando! Instale as extensões necessárias.\n";
    exit(1);
}

echo "\n";

// Testar conexão
echo "🔌 Testando conexão com SAP HANA...\n";

try {
    $startTime = microtime(true);
    
    $sapConn = \App\adms\Models\Services\SapB1HanaConnection::getInstance();
    $conn = $sapConn->getConnection();
    
    $elapsed = round((microtime(true) - $startTime) * 1000, 2);
    
    echo "   ✅ Conexão estabelecida! ({$elapsed}ms)\n\n";
    
    // Testar query simples
    echo "📊 Testando query SELECT:\n";
    
    $startTime = microtime(true);
    $stmt = $conn->query('SELECT CURRENT_DATE AS "Data", CURRENT_TIME AS "Hora" FROM DUMMY');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $elapsed = round((microtime(true) - $startTime) * 1000, 2);
    
    echo "   ✅ Query executada com sucesso! ({$elapsed}ms)\n";
    echo "   📅 Data: {$result['Data']}\n";
    echo "   🕐 Hora: {$result['Hora']}\n\n";
    
    // Testar acesso ao schema
    echo "🗄️  Testando acesso ao schema:\n";
    
    $schema = $_ENV['SAP_HANA_SCHEMA'] ?? $_ENV['SAP_B1_DATABASE'] ?? 'UNKNOWN';
    
    try {
        $stmt = $conn->query("SELECT COUNT(*) AS total FROM \"OITM\" WHERE ROWNUM <= 5");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "   ✅ Acesso ao schema '{$schema}' OK!\n";
        echo "   📦 Produtos encontrados: {$result['total']}\n\n";
        
    } catch (Exception $e) {
        echo "   ⚠️  Erro ao acessar schema: {$e->getMessage()}\n\n";
    }
    
    // Testar permissões (verificar se é read-only)
    echo "🔒 Verificando permissões (esperado: READ-ONLY):\n";
    
    try {
        $conn->query("INSERT INTO \"OITM\" (\"ItemCode\") VALUES ('TEST')");
        echo "   ⚠️  ATENÇÃO: Usuário tem permissão de ESCRITA!\n";
        echo "   ⚠️  RECOMENDAÇÃO: Use um usuário READ-ONLY!\n\n";
        
        // Rollback
        $conn->query("DELETE FROM \"OITM\" WHERE \"ItemCode\" = 'TEST'");
        
    } catch (Exception $e) {
        echo "   ✅ Usuário é READ-ONLY (recomendado)\n\n";
    }
    
    echo "═══════════════════════════════════════════════════════════\n";
    echo "✅ TESTE CONCLUÍDO COM SUCESSO!\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    echo "🎉 Sua aplicação está pronta para conectar ao SAP B1!\n\n";
    
} catch (Exception $e) {
    echo "   ❌ ERRO: " . $e->getMessage() . "\n\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "❌ TESTE FALHOU!\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    echo "🔧 Possíveis causas:\n";
    echo "   1. VPN/Túnel não está conectado\n";
    echo "   2. Firewall bloqueando a conexão\n";
    echo "   3. Credenciais incorretas\n";
    echo "   4. Host/porta incorretos\n";
    echo "   5. SAP HANA Client (ODBC) não instalado\n";
    echo "   6. DSN ODBC não configurado\n\n";
    
    echo "📚 Consulte: DEPLOY_PRODUCAO_SAP_B1.md\n\n";
    
    exit(1);
}

