<?php
echo "\n🔐 TESTE HDBODBC COM CREDENCIAIS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Solicitar credenciais
echo "Digite as credenciais do banco HANA:\n\n";

echo "Usuário HANA [SYSTEM]: ";
$username = trim(fgets(STDIN));
if (empty($username)) $username = 'SYSTEM';

echo "Senha HANA: ";
$password = trim(fgets(STDIN));

if (empty($password)) {
    echo "\n❌ Senha não pode estar vazia!\n";
    exit(1);
}

echo "\n📊 Testando conexão...\n";
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
        echo "Primeiros itens:\n";
        foreach ($results as $row) {
            echo "  - {$row['ItemCode']}: {$row['ItemName']}\n";
        }
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "🎉 SUCESSO! HDBODBC ESTÁ FUNCIONANDO!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    echo "Agora adicione as credenciais no .env:\n";
    echo "  SAP_HANA_DSN=SBO_TIARAJU_HOM\n";
    echo "  SAP_HANA_USERNAME=$username\n";
    echo "  SAP_HANA_PASSWORD=sua_senha_aqui\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERRO\n\n";
    echo "Mensagem: " . $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'authentication failed') !== false) {
        echo "⚠️  Usuário ou senha incorretos!\n";
    } elseif (strpos($e->getMessage(), 'Cannot establish connection') !== false) {
        echo "⚠️  Não consegue conectar no servidor HANA.\n";
        echo "  Verifique se o servidor está acessível.\n";
    }
    
    exit(1);
}

