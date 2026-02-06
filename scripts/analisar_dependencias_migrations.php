<?php
/**
 * Script para analisar dependências entre migrations e identificar problemas de ordem
 */

$migrationsDir = __DIR__ . '/../database/migrations';
$migrations = [];

// Ler todas as migrations
$files = glob($migrationsDir . '/*.php');
foreach ($files as $file) {
    $filename = basename($file);
    if (preg_match('/^(\d{14})_(.+)\.php$/', $filename, $matches)) {
        $timestamp = $matches[1];
        $name = $matches[2];
        
        $content = file_get_contents($file);
        
        // Extrair informações
        $creates = [];
        $modifies = [];
        $dependsOn = [];
        
        // Tabelas criadas
        if (preg_match_all("/createTable\(['\"](\w+)['\"]|->table\(['\"](\w+)['\"].*->create\(\)/", $content, $matches2)) {
            foreach ($matches2[1] as $table) {
                if (!empty($table)) $creates[] = $table;
            }
            foreach ($matches2[2] as $table) {
                if (!empty($table)) $creates[] = $table;
            }
        }
        
        // Tabelas modificadas (hasTable mas não create)
        if (preg_match_all("/hasTable\(['\"](\w+)['\"]/", $content, $matches3)) {
            foreach ($matches3[1] as $table) {
                if (!in_array($table, $creates)) {
                    $modifies[] = $table;
                }
            }
        }
        
        // Foreign keys (dependências)
        if (preg_match_all("/addForeignKey\([^,]+,\s*['\"](\w+)['\"]/", $content, $matches4)) {
            foreach ($matches4[1] as $table) {
                $dependsOn[] = $table;
            }
        }
        
        $migrations[] = [
            'file' => $filename,
            'timestamp' => $timestamp,
            'name' => $name,
            'creates' => array_unique($creates),
            'modifies' => array_unique($modifies),
            'dependsOn' => array_unique($dependsOn)
        ];
    }
}

// Ordenar por timestamp
usort($migrations, function($a, $b) {
    return strcmp($a['timestamp'], $b['timestamp']);
});

// Analisar problemas
$problems = [];
$tableCreationOrder = [];

foreach ($migrations as $migration) {
    // Registrar quando cada tabela é criada
    foreach ($migration['creates'] as $table) {
        if (!isset($tableCreationOrder[$table])) {
            $tableCreationOrder[$table] = $migration['timestamp'];
        }
    }
    
    // Verificar se modifica tabela que não existe ainda
    foreach ($migration['modifies'] as $table) {
        if (!isset($tableCreationOrder[$table])) {
            $problems[] = [
                'type' => 'MODIFIES_NONEXISTENT',
                'migration' => $migration['file'],
                'timestamp' => $migration['timestamp'],
                'table' => $table,
                'message' => "Migration {$migration['file']} tenta modificar tabela '{$table}' que ainda não foi criada"
            ];
        } elseif ($tableCreationOrder[$table] > $migration['timestamp']) {
            $problems[] = [
                'type' => 'MODIFIES_BEFORE_CREATION',
                'migration' => $migration['file'],
                'timestamp' => $migration['timestamp'],
                'table' => $table,
                'created_at' => $tableCreationOrder[$table],
                'message' => "Migration {$migration['file']} ({$migration['timestamp']}) tenta modificar '{$table}' que só é criada em {$tableCreationOrder[$table]}"
            ];
        }
    }
    
    // Verificar dependências de foreign keys
    foreach ($migration['dependsOn'] as $table) {
        if (!isset($tableCreationOrder[$table])) {
            $problems[] = [
                'type' => 'FK_TO_NONEXISTENT',
                'migration' => $migration['file'],
                'timestamp' => $migration['timestamp'],
                'table' => $table,
                'message' => "Migration {$migration['file']} tem FK para tabela '{$table}' que ainda não foi criada"
            ];
        } elseif ($tableCreationOrder[$table] > $migration['timestamp']) {
            $problems[] = [
                'type' => 'FK_BEFORE_CREATION',
                'migration' => $migration['file'],
                'timestamp' => $migration['timestamp'],
                'table' => $table,
                'created_at' => $tableCreationOrder[$table],
                'message' => "Migration {$migration['file']} ({$migration['timestamp']}) tem FK para '{$table}' criada em {$tableCreationOrder[$table]}"
            ];
        }
    }
}

// Exibir resultados
echo "=== ANÁLISE DE DEPENDÊNCIAS DE MIGRATIONS ===\n\n";

echo "PROBLEMAS ENCONTRADOS:\n";
echo str_repeat("=", 80) . "\n";
if (empty($problems)) {
    echo "✅ Nenhum problema encontrado!\n";
} else {
    foreach ($problems as $problem) {
        echo "❌ {$problem['message']}\n";
        if (isset($problem['created_at'])) {
            echo "   → Tabela será criada em: {$problem['created_at']}\n";
        }
        echo "\n";
    }
}

echo "\n\nORDEM DE CRIAÇÃO DAS TABELAS:\n";
echo str_repeat("=", 80) . "\n";
asort($tableCreationOrder);
foreach ($tableCreationOrder as $table => $timestamp) {
    echo sprintf("%-14s %s\n", $timestamp, $table);
}

echo "\n\nMIGRATIONS QUE PRECISAM SER REORGANIZADAS:\n";
echo str_repeat("=", 80) . "\n";
$needsReorder = [];
foreach ($problems as $problem) {
    if (in_array($problem['type'], ['MODIFIES_BEFORE_CREATION', 'FK_BEFORE_CREATION'])) {
        $needsReorder[] = [
            'file' => $problem['migration'],
            'current_timestamp' => $problem['timestamp'],
            'should_be_after' => $problem['created_at'],
            'table' => $problem['table']
        ];
    }
}

if (!empty($needsReorder)) {
    foreach ($needsReorder as $item) {
        echo "📝 {$item['file']}\n";
        echo "   Atual: {$item['current_timestamp']}\n";
        echo "   Deve ser depois de: {$item['should_be_after']} (criação de {$item['table']})\n";
        echo "   Sugestão: " . date('YmdHis', strtotime($item['should_be_after'] . ' +1 second')) . "\n\n";
    }
} else {
    echo "✅ Nenhuma migration precisa ser reorganizada!\n";
}

