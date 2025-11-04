<?php
echo "\n🔐 TESTE FINAL - HDBODBC\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ═══════════════════════════════════════════════════════════════
// EDITE AQUI: Coloque suas credenciais do HANA
// ═══════════════════════════════════════════════════════════════
$username = "SYSTEM";           // ← Usuário do HANA
$password = "COLOQUE_SENHA_AQUI"; // ← SENHA DO HANA (não do SAP B1!)
// ═══════════════════════════════════════════════════════════════

if ($password === "COLOQUE_SENHA_AQUI") {
    echo "⚠️  ATENÇÃO: Você precisa editar o arquivo!\n\n";
    echo "Abra: scripts/test_final_hana.php\n";
    echo "Linha 9: Coloque a senha do HANA\n";
    echo "Salve e execute novamente!\n\n";
    exit(1);
}

echo "Usuário: $username\n";
echo "DSN: SBO_TIARAJU_HOM\n\n";

echo "📊 Testando conexão...\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $dsn = "odbc:SBO_TIARAJU_HOM";
    $pdo = new PDO($dsn, $username, $password);
    
    echo "✅ CONECTADO COM SUCESSO!\n\n";
    
    // Testar query
    echo "Executando query de teste... ";
    $stmt = $pdo->query('SELECT TOP 5 "ItemCode", "ItemName" FROM OITM');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ OK\n";
    echo "Registros retornados: " . count($results) . "\n\n";
    
    if (!empty($results)) {
        echo "Primeiros itens do SAP:\n";
        foreach ($results as $row) {
            echo "  • {$row['ItemCode']}: {$row['ItemName']}\n";
        }
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "🎉 PERFEITO! HDBODBC FUNCIONANDO 100%!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    echo "📋 PRÓXIMO PASSO: Adicione no .env\n";
    echo "───────────────────────────────────────────────────────────────\n";
    echo "SAP_HANA_DSN=SBO_TIARAJU_HOM\n";
    echo "SAP_HANA_USERNAME=$username\n";
    echo "SAP_HANA_PASSWORD=sua_senha_aqui\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERRO\n\n";
    echo "Mensagem: " . $e->getMessage() . "\n\n";
    
    if (stripos($e->getMessage(), 'authentication') !== false) {
        echo "⚠️  Usuário ou senha INCORRETOS!\n";
        echo "  Verifique as credenciais do HANA.\n\n";
    } elseif (stripos($e->getMessage(), 'connection') !== false) {
        echo "⚠️  Não consegue conectar no servidor.\n";
        echo "  Verifique se o servidor HANA está acessível.\n\n";
    }
    
    exit(1);
}

