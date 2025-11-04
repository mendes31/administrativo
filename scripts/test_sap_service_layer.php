<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

use App\adms\Models\Services\SapB1ServiceLayer;

echo "\n🧪 TESTE DE CONEXÃO - SAP SERVICE LAYER\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$sap = new SapB1ServiceLayer();

// Teste 1: Login
echo "📊 Teste 1: Login\n";
echo "───────────────────────────────────────────────────────────────\n";
$loginSuccess = $sap->login();

if ($loginSuccess) {
    echo "✅ Login realizado com sucesso!\n\n";
} else {
    echo "❌ Falha no login!\n";
    echo "  Verifique as credenciais no .env:\n";
    echo "  - SAP_SL_URL\n";
    echo "  - SAP_SL_USERNAME\n";
    echo "  - SAP_SL_PASSWORD\n";
    echo "  - SAP_SL_COMPANY\n\n";
    exit(1);
}

// Teste 2: Query SQL Simples
echo "📊 Teste 2: Query SQL (Primeiros 5 itens)\n";
echo "───────────────────────────────────────────────────────────────\n";
$result = $sap->executeQuery("SELECT TOP 5 \"ItemCode\", \"ItemName\", \"OnHand\" FROM OITM");

if ($result['success']) {
    echo "✅ Query executada com sucesso!\n";
    echo "  Registros retornados: " . $result['rows_count'] . "\n\n";
    echo "  Dados:\n";
    foreach ($result['data'] as $item) {
        echo "    - {$item['ItemCode']}: {$item['ItemName']} (Estoque: {$item['OnHand']})\n";
    }
    echo "\n";
} else {
    echo "❌ Erro ao executar query: " . $result['error'] . "\n\n";
}

// Teste 3: Buscar Itens via Endpoint (OData)
echo "📊 Teste 3: Buscar Itens via Endpoint OData\n";
echo "───────────────────────────────────────────────────────────────\n";
$items = $sap->getItems([
    'select' => 'ItemCode,ItemName,OnHand',
    'filter' => 'OnHand gt 0'
], 5);

if ($items['success']) {
    echo "✅ Itens recuperados com sucesso!\n";
    echo "  Total: " . $items['rows_count'] . "\n\n";
    echo "  Dados:\n";
    foreach ($items['data'] as $item) {
        echo "    - {$item['ItemCode']}: {$item['ItemName']}\n";
    }
    echo "\n";
} else {
    echo "❌ Erro: " . $items['error'] . "\n\n";
}

// Teste 4: Buscar Parceiros de Negócio
echo "📊 Teste 4: Buscar Parceiros de Negócio (Clientes)\n";
echo "───────────────────────────────────────────────────────────────\n";
$partners = $sap->getBusinessPartners([
    'select' => 'CardCode,CardName,CardType',
    'filter' => 'CardType eq \'C\''
], 5);

if ($partners['success']) {
    echo "✅ Parceiros recuperados com sucesso!\n";
    echo "  Total: " . $partners['rows_count'] . "\n\n";
    echo "  Dados:\n";
    foreach ($partners['data'] as $partner) {
        echo "    - {$partner['CardCode']}: {$partner['CardName']}\n";
    }
    echo "\n";
} else {
    echo "❌ Erro: " . $partners['error'] . "\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ Testes concluídos!\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

