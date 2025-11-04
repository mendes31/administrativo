<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

use App\adms\Models\Repository\DynamicReportsRepository;

echo "\n🧪 TESTE DE SALVAMENTO DE RELATÓRIO\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$repo = new DynamicReportsRepository();

// Teste 1: Criar relatório SQL personalizado
echo "📊 Teste 1: Salvar SQL Personalizado\n";
echo "───────────────────────────────────────────────────────────────\n";

$data = [
    'name' => 'Teste SQL - Usuários Ativos ' . date('H:i:s'),
    'description' => 'Teste de salvamento SQL personalizado',
    'query_mode' => 'custom_sql',
    'custom_sql' => 'SELECT * FROM adms_users WHERE status = "Ativo" LIMIT 10',
    'visualization_type' => 'table',
    'created_by' => 1,
    'is_public' => 0,
    'data_source' => null,
    'fields' => [],
    'filters' => [],
    'groupby' => [],
    'orderby' => [],
    'chart_config' => []
];

try {
    $reportId = $repo->create($data);
    
    if ($reportId > 0) {
        echo "✅ Relatório criado com sucesso!\n";
        echo "  ID: {$reportId}\n";
        
        // Buscar o relatório salvo
        $saved = $repo->getById($reportId);
        echo "  Nome: {$saved['name']}\n";
        echo "  Modo: {$saved['query_mode']}\n";
        echo "  SQL: {$saved['custom_sql']}\n\n";
        
        // Deletar o teste
        $repo->delete($reportId);
        echo "🗑️  Relatório de teste deletado.\n";
    } else {
        echo "❌ Erro ao criar relatório\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "  Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ Se você viu 'Relatório criado com sucesso', está FUNCIONANDO!\n";
echo "📋 Agora teste no navegador!\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

