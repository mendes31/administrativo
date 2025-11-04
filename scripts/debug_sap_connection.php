<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

use App\adms\Models\Services\SapB1ServiceLayer;

echo "\n🔍 DEBUG - CONEXÃO SAP SERVICE LAYER\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Verificar configuração
echo "📋 Configuração:\n";
echo "  URL: " . ($_ENV['SAP_SL_URL'] ?? 'NÃO CONFIGURADA') . "\n";
echo "  USERNAME: " . ($_ENV['SAP_SL_USERNAME'] ?? 'NÃO CONFIGURADA') . "\n";
echo "  PASSWORD: " . (isset($_ENV['SAP_SL_PASSWORD']) ? '***' : 'NÃO CONFIGURADA') . "\n";
echo "  COMPANY: " . ($_ENV['SAP_SL_COMPANY'] ?? 'NÃO CONFIGURADA') . "\n\n";

// Criar instância e testar
echo "📊 Testando conexão e query:\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $sap = new SapB1ServiceLayer();
    
    echo "1. Tentando login... ";
    $loginSuccess = $sap->login();
    
    if ($loginSuccess) {
        echo "✅ OK\n\n";
        
        echo "2. Executando query de teste... ";
        $sql = 'SELECT TOP 5 "ItemCode", "ItemName" FROM OITM';
        $result = $sap->executeQuery($sql);
        
        if ($result['success']) {
            echo "✅ OK\n";
            echo "   Registros retornados: " . ($result['rows_count'] ?? 0) . "\n\n";
            
            if (!empty($result['data'])) {
                echo "   Dados:\n";
                foreach ($result['data'] as $item) {
                    echo "   - " . ($item['ItemCode'] ?? 'N/A') . ": " . ($item['ItemName'] ?? 'N/A') . "\n";
                }
            }
        } else {
            echo "❌ ERRO\n";
            echo "   Erro: " . ($result['error'] ?? 'Desconhecido') . "\n";
        }
    } else {
        echo "❌ FALHOU\n";
        echo "   Não foi possível fazer login.\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEÇÃO\n";
    echo "   Erro: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";

