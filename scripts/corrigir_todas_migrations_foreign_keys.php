<?php
/**
 * Script para identificar e corrigir migrations com problemas de foreign keys
 * 
 * Este script analisa todas as migrations e identifica aquelas que:
 * 1. Tentam adicionar foreign keys diretamente no create()
 * 2. Não verificam se as tabelas referenciadas existem
 * 3. Não tratam erros adequadamente
 * 
 * Uso: php scripts/corrigir_todas_migrations_foreign_keys.php
 */

$migrationsDir = __DIR__ . '/../database/migrations';
$migrations = glob($migrationsDir . '/*.php');

$problematicMigrations = [];

foreach ($migrations as $migrationFile) {
    $content = file_get_contents($migrationFile);
    $filename = basename($migrationFile);
    
    // Verificar se tem addForeignKey
    if (preg_match('/addForeignKey/i', $content)) {
        // Verificar se está dentro de create() ou update() sem verificação adequada
        $hasTableCheck = preg_match('/hasTable.*referenced|hasTable.*users|hasTable.*departments/i', $content);
        $hasTryCatch = preg_match('/try\s*\{.*addForeignKey.*catch/i', $content);
        $hasCreateWithFk = preg_match('/addForeignKey.*->create\(\)|->create\(\).*addForeignKey/i', $content);
        
        if ($hasCreateWithFk || (!$hasTableCheck && !$hasTryCatch)) {
            $problematicMigrations[] = [
                'file' => $filename,
                'path' => $migrationFile,
                'issue' => $hasCreateWithFk ? 'FK no create()' : 'FK sem verificação adequada'
            ];
        }
    }
}

if (empty($problematicMigrations)) {
    echo "✅ Nenhuma migration problemática encontrada!\n";
} else {
    echo "⚠️  Encontradas " . count($problematicMigrations) . " migration(s) com possíveis problemas de foreign keys:\n\n";
    foreach ($problematicMigrations as $migration) {
        echo "📄 {$migration['file']}\n";
        echo "   Problema: {$migration['issue']}\n";
        echo "   Caminho: {$migration['path']}\n\n";
    }
    echo "💡 Essas migrations podem precisar ser corrigidas manualmente.\n";
    echo "   Padrão recomendado: Criar tabela sem FK, adicionar FK depois com verificação.\n";
}

