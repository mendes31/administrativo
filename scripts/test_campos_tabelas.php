<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

use App\adms\Models\Repository\DynamicReportsRepository;

echo "\n🧪 TESTE DE CAMPOS DAS TABELAS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$repo = new DynamicReportsRepository();
$tables = $repo->getAvailableTables();

echo "📊 Total de tabelas carregadas: " . count($tables) . "\n\n";

// Testar tabela específica: adms_access_levels
if (isset($tables['adms_access_levels'])) {
    echo "✅ Tabela 'adms_access_levels' encontrada!\n";
    echo "  Label: " . $tables['adms_access_levels']['label'] . "\n";
    echo "  Conexão: " . $tables['adms_access_levels']['connection'] . "\n";
    echo "  Campos (" . count($tables['adms_access_levels']['fields']) . "):\n";
    
    foreach ($tables['adms_access_levels']['fields'] as $fieldName => $fieldLabel) {
        echo "    - $fieldName → $fieldLabel\n";
    }
} else {
    echo "❌ Tabela 'adms_access_levels' NÃO encontrada!\n";
}

echo "\n───────────────────────────────────────────────────────────────\n\n";

// Testar tabela adms_users
if (isset($tables['adms_users'])) {
    echo "✅ Tabela 'adms_users' encontrada!\n";
    echo "  Label: " . $tables['adms_users']['label'] . "\n";
    echo "  Conexão: " . $tables['adms_users']['connection'] . "\n";
    echo "  Campos (" . count($tables['adms_users']['fields']) . "):\n";
    
    $count = 0;
    foreach ($tables['adms_users']['fields'] as $fieldName => $fieldLabel) {
        echo "    - $fieldName → $fieldLabel\n";
        $count++;
        if ($count >= 10) {
            echo "    ... (mostrando apenas primeiros 10)\n";
            break;
        }
    }
} else {
    echo "❌ Tabela 'adms_users' NÃO encontrada!\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";

