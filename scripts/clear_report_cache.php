<?php
/**
 * Script para limpar o cache de relatórios SAP
 */

$cacheDir = __DIR__ . '/../storage/cache/reports';

if (!is_dir($cacheDir)) {
    echo "Diretório de cache não existe: {$cacheDir}\n";
    exit(1);
}

$files = glob($cacheDir . '/*.json');
$count = count($files);

if ($count === 0) {
    echo "Nenhum arquivo de cache encontrado.\n";
    exit(0);
}

echo "Encontrados {$count} arquivos de cache.\n";
echo "Limpando...\n";

foreach ($files as $file) {
    if (unlink($file)) {
        echo "✓ Removido: " . basename($file) . "\n";
    } else {
        echo "✗ Erro ao remover: " . basename($file) . "\n";
    }
}

echo "\n✅ Cache limpo com sucesso!\n";

