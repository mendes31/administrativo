<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

use App\adms\Models\Services\DynamicQueryBuilderService;

echo "\n🧪 TESTE DE SQL PERSONALIZADO\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$qb = new DynamicQueryBuilderService();

// Teste 1: SQL Local
echo "📊 Teste 1: SELECT * FROM adms_users LIMIT 5\n";
echo "───────────────────────────────────────────────────────────────\n";
$result = $qb->executeReport([
    'custom_sql' => 'SELECT * FROM adms_users LIMIT 5',
    'query_mode' => 'custom_sql'
]);

if ($result['success']) {
    echo "✅ Sucesso!\n";
    echo "  Registros: " . $result['rows_count'] . "\n";
    echo "  Tempo: " . $result['execution_time'] . "s\n";
    echo "  Conexão: " . $result['connection_type'] . "\n";
    echo "  SQL: " . $result['sql'] . "\n\n";
    echo "  Dados:\n";
    foreach ($result['data'] as $row) {
        echo "    - " . ($row['name'] ?? 'N/A') . "\n";
    }
} else {
    echo "❌ Erro: " . $result['error'] . "\n";
    echo "  SQL: " . ($result['sql'] ?? 'N/A') . "\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";

