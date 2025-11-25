<?php
/**
 * Teste do log do SAP API Service
 */

require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

require_once __DIR__ . '/../app/adms/Models/Services/SapReportApiService.php';

use App\adms\Models\Services\SapReportApiService;

echo "=== TESTE DO SAP API SERVICE E LOG ===\n\n";

try {
    $service = new SapReportApiService();
    echo "✅ Serviço instanciado\n\n";
    
    echo "Testando com SQL: SELECT * FROM OITM\n";
    $result = $service->execute('SELECT * FROM OITM');
    
    echo "\n✅ Execução concluída!\n";
    echo "Success: " . ($result['success'] ? 'SIM' : 'NÃO') . "\n";
    if (isset($result['data'])) {
        echo "Registros retornados: " . count($result['data']) . "\n";
    }
    if (isset($result['error'])) {
        echo "Erro: " . $result['error'] . "\n";
    }
    
    // Verificar se o log foi criado
    $logFile = __DIR__ . '/../app/logs/sap_api.log';
    echo "\n=== VERIFICAÇÃO DO LOG ===\n";
    echo "Caminho do log: {$logFile}\n";
    echo "Existe: " . (file_exists($logFile) ? 'SIM' : 'NÃO') . "\n";
    
    if (file_exists($logFile)) {
        echo "Tamanho: " . filesize($logFile) . " bytes\n";
        echo "\nÚltimas 20 linhas do log:\n";
        echo str_repeat("-", 50) . "\n";
        $lines = file($logFile);
        $lastLines = array_slice($lines, -20);
        echo implode("", $lastLines);
    } else {
        echo "❌ Arquivo de log não foi criado!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}

