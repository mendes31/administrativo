<?php
/**
 * Script de diagnóstico para TrainingKpiDashboard
 * 
 * Execute este script na produção para identificar problemas
 * 
 * USO:
 * php scripts/diagnostico_training_kpi_dashboard.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

echo "=== DIAGNÓSTICO TRAINING KPI DASHBOARD ===\n\n";

// 1. Verificar se as classes existem
echo "1. Verificando classes...\n";
$classes = [
    'App\adms\Controllers\trainings\TrainingKpiDashboard',
    'App\adms\Models\Repository\TrainingUsersRepository',
    'App\adms\Models\Repository\TrainingApplicationsRepository',
    'App\adms\Models\Repository\TrainingsRepository',
    'App\adms\Models\Repository\UsersRepository',
    'App\adms\Models\Repository\DepartmentsRepository',
    'App\adms\Models\Repository\PositionsRepository',
    'App\adms\Controllers\Services\PageLayoutService',
    'App\adms\Views\Services\LoadViewService',
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "  ✓ {$class}\n";
    } else {
        echo "  ✗ {$class} - NÃO ENCONTRADA!\n";
    }
}

// 2. Verificar se a view existe
echo "\n2. Verificando view...\n";
$viewPath = __DIR__ . '/../app/adms/Views/trainings/kpiDashboard.php';
if (file_exists($viewPath)) {
    echo "  ✓ View existe: {$viewPath}\n";
} else {
    echo "  ✗ View NÃO encontrada: {$viewPath}\n";
}

// 3. Verificar conexão com banco de dados
echo "\n3. Verificando conexão com banco...\n";
try {
    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];
    $port = $_ENV['DB_PORT'] ?? 3306;
    
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "  ✓ Conexão com banco OK\n";
} catch (PDOException $e) {
    echo "  ✗ Erro na conexão: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Verificar se as tabelas existem
echo "\n4. Verificando tabelas...\n";
$tables = [
    'adms_training_users',
    'adms_training_applications',
    'adms_trainings',
    'adms_users',
    'adms_departments',
    'adms_positions',
];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ Tabela {$table} existe\n";
        } else {
            echo "  ✗ Tabela {$table} NÃO existe\n";
        }
    } catch (PDOException $e) {
        echo "  ✗ Erro ao verificar {$table}: " . $e->getMessage() . "\n";
    }
}

// 5. Testar métodos do repository
echo "\n5. Testando métodos do repository...\n";
try {
    $repo = new App\adms\Models\Repository\TrainingUsersRepository();
    
    // Testar getSummaryAll
    try {
        $summary = $repo->getSummaryAll();
        echo "  ✓ getSummaryAll() - OK (retornou " . count($summary) . " chaves)\n";
        print_r($summary);
    } catch (Exception $e) {
        echo "  ✗ getSummaryAll() - ERRO: " . $e->getMessage() . "\n";
        echo "    Trace: " . $e->getTraceAsString() . "\n";
    }
    
    // Testar getStatusCounts
    try {
        $statusCounts = $repo->getStatusCounts();
        echo "  ✓ getStatusCounts() - OK (retornou " . count($statusCounts) . " registros)\n";
    } catch (Exception $e) {
        echo "  ✗ getStatusCounts() - ERRO: " . $e->getMessage() . "\n";
        echo "    Trace: " . $e->getTraceAsString() . "\n";
    }
    
    // Testar getMonthlyRealizations
    try {
        $monthly = $repo->getMonthlyRealizations();
        echo "  ✓ getMonthlyRealizations() - OK (retornou " . count($monthly) . " registros)\n";
    } catch (Exception $e) {
        echo "  ✗ getMonthlyRealizations() - ERRO: " . $e->getMessage() . "\n";
        echo "    Trace: " . $e->getTraceAsString() . "\n";
    }
    
} catch (Exception $e) {
    echo "  ✗ Erro ao instanciar repository: " . $e->getMessage() . "\n";
    echo "    Trace: " . $e->getTraceAsString() . "\n";
}

// 6. Testar PageLayoutService
echo "\n6. Testando PageLayoutService...\n";
try {
    $pageLayout = new App\adms\Controllers\Services\PageLayoutService();
    $data = [
        'title_head' => 'Teste',
        'menu' => 'training-kpi-dashboard',
        'buttonPermission' => ['TrainingKpiDashboard'],
    ];
    $result = $pageLayout->configurePageElements($data);
    echo "  ✓ PageLayoutService->configurePageElements() - OK\n";
} catch (Exception $e) {
    echo "  ✗ PageLayoutService - ERRO: " . $e->getMessage() . "\n";
    echo "    Trace: " . $e->getTraceAsString() . "\n";
}

// 7. Verificar logs de erro do PHP
echo "\n7. Verificando configuração de erros...\n";
echo "  display_errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "\n";
echo "  log_errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "\n";
echo "  error_log: " . ini_get('error_log') . "\n";

// 8. Testar instanciação do controller
echo "\n8. Testando instanciação do controller...\n";
try {
    $controller = new App\adms\Controllers\trainings\TrainingKpiDashboard();
    echo "  ✓ Controller instanciado com sucesso\n";
    
    // Tentar chamar getDashboardData usando reflexão
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('getDashboardData');
    $method->setAccessible(true);
    
    try {
        $dashboardData = $method->invoke($controller);
        echo "  ✓ getDashboardData() executado com sucesso\n";
        echo "    Chaves retornadas: " . implode(', ', array_keys($dashboardData)) . "\n";
    } catch (Exception $e) {
        echo "  ✗ getDashboardData() - ERRO: " . $e->getMessage() . "\n";
        echo "    Trace: " . $e->getTraceAsString() . "\n";
    }
    
} catch (Exception $e) {
    echo "  ✗ Erro ao instanciar controller: " . $e->getMessage() . "\n";
    echo "    Trace: " . $e->getTraceAsString() . "\n";
}

// 9. Verificar permissões
echo "\n9. Verificando página no banco...\n";
try {
    $stmt = $pdo->prepare("SELECT * FROM adms_pages WHERE controller = 'TrainingKpiDashboard'");
    $stmt->execute();
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($page) {
        echo "  ✓ Página encontrada no banco:\n";
        echo "    ID: {$page['id']}\n";
        echo "    Nome: {$page['name']}\n";
        echo "    Controller: {$page['controller']}\n";
        echo "    Status: " . ($page['page_status'] ? 'Ativo' : 'Inativo') . "\n";
    } else {
        echo "  ✗ Página NÃO encontrada no banco!\n";
    }
} catch (PDOException $e) {
    echo "  ✗ Erro ao verificar página: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";

