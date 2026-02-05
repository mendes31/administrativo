<?php
/**
 * Script para testar se o cache está funcionando
 * 
 * Uso: php scripts/test_cache.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\adms\Models\Services\QueryCacheService;
use App\adms\Models\Repository\TrainingsRepository;

echo "🧪 === TESTE DE CACHE ===\n\n";

// Testar QueryCacheService diretamente
echo "1. Testando QueryCacheService...\n";
$cacheService = new QueryCacheService();

// Verificar se a pasta existe
$cacheDir = __DIR__ . '/../storage/cache/queries';
echo "   📁 Pasta de cache: {$cacheDir}\n";
echo "   📁 Existe? " . (is_dir($cacheDir) ? "✅ SIM" : "❌ NÃO") . "\n";
echo "   📁 É gravável? " . (is_writable($cacheDir) ? "✅ SIM" : "❌ NÃO") . "\n";

// Tentar escrever um arquivo de teste
$testKey = 'test_cache';
$testData = ['test' => 'data', 'timestamp' => time()];
echo "\n2. Tentando salvar cache de teste...\n";
$saved = $cacheService->put($testKey, $testData);
echo "   💾 Resultado: " . ($saved ? "✅ SUCESSO" : "❌ FALHOU") . "\n";

// Verificar se o arquivo foi criado
$testFile = $cacheDir . '/' . $testKey . '.json';
echo "   📄 Arquivo criado? " . (file_exists($testFile) ? "✅ SIM" : "❌ NÃO") . "\n";
if (file_exists($testFile)) {
    echo "   📄 Tamanho: " . filesize($testFile) . " bytes\n";
    echo "   📄 Conteúdo: " . file_get_contents($testFile) . "\n";
}

// Tentar ler o cache
echo "\n3. Tentando ler cache de teste...\n";
$cached = $cacheService->get($testKey);
echo "   📖 Resultado: " . ($cached !== null ? "✅ SUCESSO" : "❌ FALHOU") . "\n";
if ($cached !== null) {
    echo "   📖 Dados: " . json_encode($cached) . "\n";
}

// Testar com repositório real
echo "\n4. Testando com TrainingsRepository...\n";
try {
    $repo = new TrainingsRepository();
    echo "   🔍 Chamando getAllTrainingsSelect()...\n";
    $result = $repo->getAllTrainingsSelect();
    echo "   ✅ Método executado com sucesso\n";
    echo "   📊 Registros retornados: " . count($result) . "\n";
    
    // Verificar se o cache foi criado
    $cacheFile = $cacheDir . '/trainings_select_all.json';
    echo "   📄 Cache criado? " . (file_exists($cacheFile) ? "✅ SIM" : "❌ NÃO") . "\n";
    if (file_exists($cacheFile)) {
        echo "   📄 Tamanho: " . filesize($cacheFile) . " bytes\n";
    }
} catch (Exception $e) {
    echo "   ❌ ERRO: " . $e->getMessage() . "\n";
    echo "   📋 Trace: " . $e->getTraceAsString() . "\n";
}

// Listar todos os arquivos de cache
echo "\n5. Arquivos na pasta de cache:\n";
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*.json');
    if (empty($files)) {
        echo "   📂 Pasta vazia\n";
    } else {
        foreach ($files as $file) {
            echo "   📄 " . basename($file) . " (" . filesize($file) . " bytes)\n";
        }
    }
} else {
    echo "   ❌ Pasta não existe\n";
}

// Limpar arquivo de teste
if (file_exists($testFile)) {
    unlink($testFile);
    echo "\n🧹 Arquivo de teste removido\n";
}

echo "\n✅ Teste concluído!\n";

