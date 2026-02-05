<?php
/**
 * Script para limpar cache de queries frequentes
 * 
 * Uso: php scripts/clear_query_cache.php [opcional: prefixo]
 * 
 * Exemplos:
 *   php scripts/clear_query_cache.php              # Limpa todo o cache
 *   php scripts/clear_query_cache.php trainings    # Limpa apenas cache de treinamentos
 *   php scripts/clear_query_cache.php users        # Limpa apenas cache de usuários
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\adms\Models\Services\QueryCacheService;

$cacheService = new QueryCacheService();

if (isset($argv[1])) {
    // Limpar por prefixo
    $prefix = $argv[1];
    $count = $cacheService->clearByPrefix($prefix);
    echo "✅ Cache limpo para prefixo '{$prefix}': {$count} arquivo(s) removido(s)\n";
} else {
    // Limpar tudo
    $count = $cacheService->clear();
    echo "✅ Todo o cache de queries foi limpo: {$count} arquivo(s) removido(s)\n";
}

