<?php
/**
 * Script para corrigir backup SQL adicionando INSERT IGNORE
 * 
 * Uso: php scripts/corrigir_backup_insert_ignore.php backup.sql backup_corrigido.sql
 */

if ($argc < 3) {
    echo "❌ Uso: php scripts/corrigir_backup_insert_ignore.php <arquivo_origem> <arquivo_destino>\n";
    echo "   Exemplo: php scripts/corrigir_backup_insert_ignore.php backup.sql backup_ignore.sql\n";
    exit(1);
}

$arquivoOrigem = $argv[1];
$arquivoDestino = $argv[2];

if (!file_exists($arquivoOrigem)) {
    echo "❌ Arquivo não encontrado: {$arquivoOrigem}\n";
    exit(1);
}

echo "📖 Lendo arquivo: {$arquivoOrigem}\n";
$conteudo = file_get_contents($arquivoOrigem);

echo "🔧 Substituindo INSERT INTO por INSERT IGNORE INTO...\n";

// Contar quantos INSERT INTO existem antes
$contagemAntes = preg_match_all('/INSERT\s+INTO/i', $conteudo);

// Substituir todas as variações de INSERT INTO
$conteudoCorrigido = preg_replace('/INSERT\s+INTO/i', 'INSERT IGNORE INTO', $conteudo);

// Verificar se já tem INSERT IGNORE (para não duplicar)
$conteudoCorrigido = preg_replace('/INSERT\s+IGNORE\s+IGNORE\s+INTO/i', 'INSERT IGNORE INTO', $conteudoCorrigido);

echo "💾 Salvando arquivo corrigido: {$arquivoDestino}\n";
file_put_contents($arquivoDestino, $conteudoCorrigido);

$tamanhoOriginal = filesize($arquivoOrigem);
$tamanhoCorrigido = filesize($arquivoDestino);
$substituicoes = substr_count($conteudoCorrigido, 'INSERT IGNORE INTO');

echo "\n✅ Arquivo corrigido com sucesso!\n";
echo "   📊 Tamanho original: " . number_format($tamanhoOriginal) . " bytes\n";
echo "   📊 Tamanho corrigido: " . number_format($tamanhoCorrigido) . " bytes\n";
echo "   🔄 INSERT INTO encontrados: {$contagemAntes}\n";
echo "   🔄 INSERT IGNORE INTO no arquivo final: {$substituicoes}\n";
echo "\n💡 Agora você pode importar o arquivo: {$arquivoDestino}\n";
echo "   📝 Este arquivo ignora registros duplicados (criados pelas seeds)\n";
echo "   ✅ Apenas registros novos serão inseridos\n";

