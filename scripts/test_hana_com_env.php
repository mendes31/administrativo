<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

echo "\n🔐 TESTE HDBODBC - USANDO CREDENCIAIS DO .ENV\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Ler credenciais do .env
$dsn_name = $_ENV['SAP_HANA_DSN'] ?? 'SBO_TIARAJU_HOM';
$username = $_ENV['SAP_HANA_USERNAME'] ?? null;
$password = $_ENV['SAP_HANA_PASSWORD'] ?? null;

// Verificar se as credenciais existem
echo "📋 Credenciais encontradas no .env:\n";
echo "  DSN: " . ($dsn_name ?? '❌ NÃO CONFIGURADO') . "\n";
echo "  Usuário: " . ($username ?? '❌ NÃO CONFIGURADO') . "\n";
echo "  Senha: " . ($password ? '✅ ***configurada***' : '❌ NÃO CONFIGURADA') . "\n\n";

if (!$username || !$password) {
    echo "⚠️  ERRO: Credenciais não encontradas no .env\n\n";
    echo "Adicione estas linhas no .env:\n";
    echo "  SAP_HANA_DSN=SBO_TIARAJU_HOM\n";
    echo "  SAP_HANA_USERNAME=SYSTEM\n";
    echo "  SAP_HANA_PASSWORD=sua_senha_hana\n\n";
    exit(1);
}

// Testar extensões
echo "📊 Verificando extensões PHP:\n";
echo "  PDO: " . (extension_loaded('pdo') ? '✅ Ativa' : '❌ Inativa') . "\n";
echo "  PDO_ODBC: " . (extension_loaded('pdo_odbc') ? '✅ Ativa' : '❌ Inativa') . "\n\n";

if (!extension_loaded('pdo_odbc')) {
    echo "❌ Extensão pdo_odbc não está ativa!\n";
    exit(1);
}

// Testar conexão
echo "🔌 Testando conexão...\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $dsn = "odbc:" . $dsn_name;
    $pdo = new PDO($dsn, $username, $password);
    
    echo "✅ CONECTADO COM SUCESSO!\n\n";
    
    // Definir schema padrão (importante!)
    $schema = $_ENV['SAP_HANA_SCHEMA'] ?? $_ENV['SAP_SL_COMPANY'] ?? 'SBO_TIARAJU_HOM';
    $pdo->exec("SET SCHEMA \"$schema\"");
    
    // Testar query
    echo "📝 Executando query de teste... ";
    $stmt = $pdo->query('SELECT TOP 5 "ItemCode", "ItemName", "OnHand" FROM OITM');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ OK\n";
    echo "   Registros retornados: " . count($results) . "\n\n";
    
    if (!empty($results)) {
        echo "📦 Primeiros itens do SAP B1:\n";
        foreach ($results as $row) {
            echo "   • " . $row['ItemCode'] . ": " . $row['ItemName'] . 
                 " (Estoque: " . $row['OnHand'] . ")\n";
        }
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "🎉 PERFEITO! HDBODBC FUNCIONANDO 100%!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    echo "✅ Agora você pode usar queries SQL no sistema!\n";
    echo "✅ Vá para: Relatórios Dinâmicos → SQL Personalizado\n";
    echo "✅ Digite: SELECT * FROM OITM\n";
    echo "✅ O sistema vai executar via ODBC automaticamente!\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERRO AO CONECTAR\n\n";
    echo "Mensagem: " . $e->getMessage() . "\n\n";
    
    $errorMsg = $e->getMessage();
    
    if (stripos($errorMsg, 'authentication') !== false || 
        stripos($errorMsg, 'invalid credentials') !== false ||
        stripos($errorMsg, '10') !== false) {
        echo "⚠️  CREDENCIAIS INCORRETAS!\n";
        echo "  Usuário ou senha do HANA está errada.\n";
        echo "  Verifique SAP_HANA_USERNAME e SAP_HANA_PASSWORD no .env\n\n";
    } elseif (stripos($errorMsg, 'connection') !== false ||
              stripos($errorMsg, 'server') !== false) {
        echo "⚠️  ERRO DE CONEXÃO!\n";
        echo "  Não consegue conectar no servidor HANA.\n";
        echo "  Verifique se o servidor está acessível.\n\n";
    } elseif (stripos($errorMsg, 'data source name') !== false) {
        echo "⚠️  DSN NÃO ENCONTRADO!\n";
        echo "  O DSN '$dsn_name' não existe.\n";
        echo "  Verifique em: odbcad32.exe\n\n";
    }
    
    exit(1);
}

