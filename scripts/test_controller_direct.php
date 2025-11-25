<?php
/**
 * Teste direto do controller ExecuteDynamicReport
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['report_id'] = 'preview';
$_POST['query_mode'] = 'custom_sql';
$_POST['custom_sql'] = 'SELECT * FROM OITM';
$_POST['visualization_type'] = 'table';
$_POST['force_refresh'] = '0';

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;

// Carregar autoloader e env
require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

echo "=== TESTE DIRETO DO CONTROLLER ===\n\n";
echo "POST data:\n";
print_r($_POST);
echo "\n";

// Capturar output
ob_start();

try {
    require_once __DIR__ . '/../app/adms/Controllers/reports/ExecuteDynamicReport.php';
    $controller = new \App\adms\Controllers\reports\ExecuteDynamicReport();
    $controller->index();
    
    $output = ob_get_clean();
    echo "Output do controller:\n";
    echo $output . "\n";
    
} catch (\Throwable $e) {
    ob_end_clean();
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

// Verificar logs
echo "\n=== VERIFICANDO LOGS ===\n";
$logFile = __DIR__ . '/../app/logs/sap_api.log';
$logFileAlt = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sap_api.log';

if (file_exists($logFile)) {
    echo "\nLog principal (últimas 20 linhas):\n";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20);
    echo implode("", $lastLines);
} else {
    echo "\n❌ Log principal não existe!\n";
}

if (file_exists($logFileAlt)) {
    echo "\nLog alternativo (últimas 20 linhas):\n";
    $lines = file($logFileAlt);
    $lastLines = array_slice($lines, -20);
    echo implode("", $lastLines);
} else {
    echo "\n❌ Log alternativo não existe!\n";
}

