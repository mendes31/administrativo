<?php
echo "\n🧪 TESTE DE CONEXÃO - HDBODBC\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Verificar se extensão PDO_ODBC está ativa
echo "📋 Verificando extensões PHP:\n";
echo "  PDO: " . (extension_loaded('pdo') ? '✅ Ativa' : '❌ Inativa') . "\n";
echo "  PDO_ODBC: " . (extension_loaded('pdo_odbc') ? '✅ Ativa' : '❌ Inativa') . "\n\n";

if (!extension_loaded('pdo_odbc')) {
    echo "⚠️  ERRO: Extensão pdo_odbc não está ativa!\n\n";
    echo "Ative no php.ini:\n";
    echo "  extension=pdo_odbc\n\n";
    exit(1);
}

// Testar conexão
echo "📊 Testando conexão ODBC:\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    // Opção 1: Usar DSN
    echo "1. Tentando via DSN (SBO_TIARAJU_HOM)... ";
    $dsn = "odbc:SBO_TIARAJU_HOM";
    $username = "SYSTEM"; // Ajuste conforme necessário
    $password = ""; // Coloque a senha do HANA
    
    $pdo = new PDO($dsn, $username, $password);
    echo "✅ CONECTADO!\n\n";
    
    // Testar query
    echo "2. Executando query de teste... ";
    $stmt = $pdo->query('SELECT TOP 5 "ItemCode", "ItemName" FROM OITM');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ OK\n";
    echo "   Registros retornados: " . count($results) . "\n\n";
    
    if (!empty($results)) {
        echo "   Dados:\n";
        foreach ($results as $row) {
            echo "   - {$row['ItemCode']}: {$row['ItemName']}\n";
        }
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "✅ HDBODBC FUNCIONANDO PERFEITAMENTE!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERRO\n\n";
    echo "Mensagem: " . $e->getMessage() . "\n\n";
    
    echo "⚠️  Possíveis causas:\n";
    echo "  1. DSN não configurado (rode odbcad32.exe)\n";
    echo "  2. Credenciais incorretas\n";
    echo "  3. Servidor HANA inacessível\n";
    echo "  4. Driver HDBODBC não instalado\n\n";
    
    echo "Tente também:\n";
    echo "  - Verificar DSNs disponíveis:\n";
    echo "    odbc_data_source(PDO::FETCH_NUM, PDO::FETCH_COLUMN)\n\n";
    
    exit(1);
}

