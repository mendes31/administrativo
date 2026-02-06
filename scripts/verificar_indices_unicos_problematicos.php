<?php
/**
 * Script para identificar migrations com índices únicos problemáticos
 * (VARCHAR(255) ou maiores com utf8mb4 = 1020+ bytes > 767 bytes)
 * 
 * Uso: php scripts/verificar_indices_unicos_problematicos.php
 */

$migrationsDir = __DIR__ . '/../database/migrations';
$migrations = glob($migrationsDir . '/*.php');

$problematicMigrations = [];

foreach ($migrations as $migrationFile) {
    $content = file_get_contents($migrationFile);
    $filename = basename($migrationFile);
    
    // Verificar se tem índice único
    if (preg_match('/addIndex.*unique.*true/i', $content)) {
        // Verificar se tem coluna VARCHAR(255) ou sem limit (que vira 255)
        if (preg_match('/addColumn.*string.*limit.*255|addColumn.*string.*null.*false/i', $content)) {
            // Extrair informações sobre o índice único
            if (preg_match('/addIndex\(\[([^\]]+)\].*unique.*true/i', $content, $matches)) {
                $indexColumn = trim($matches[1], "'\"");
                
                // Verificar se a coluna é VARCHAR(255) ou sem limit
                $columnPattern = '/addColumn\([\'"]' . preg_quote($indexColumn, '/') . '[\'"].*string.*(?:limit.*255|limit.*\d{3,})/i';
                if (preg_match($columnPattern, $content) || preg_match('/addColumn\([\'"]' . preg_quote($indexColumn, '/') . '[\'"].*string[^,]*\)/i', $content)) {
                    $problematicMigrations[] = [
                        'file' => $filename,
                        'column' => $indexColumn,
                        'path' => $migrationFile
                    ];
                }
            }
        }
    }
}

if (empty($problematicMigrations)) {
    echo "✅ Nenhuma migration problemática encontrada!\n";
} else {
    echo "⚠️  Encontradas " . count($problematicMigrations) . " migration(s) com índices únicos problemáticos:\n\n";
    foreach ($problematicMigrations as $migration) {
        echo "📄 {$migration['file']}\n";
        echo "   Coluna: {$migration['column']} (VARCHAR 255 ou maior)\n";
        echo "   Caminho: {$migration['path']}\n\n";
    }
    echo "💡 Essas migrations precisam ser corrigidas removendo o índice único.\n";
}

