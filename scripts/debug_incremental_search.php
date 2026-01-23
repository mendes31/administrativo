<?php
/**
 * Script de debug para testar busca incremental
 * Mostra a query SQL gerada e explica o que está acontecendo
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\adms\Models\Services\DynamicQueryBuilderService;
use App\adms\Models\Services\ReportCacheService;

// Configurar logs
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
error_reporting(E_ALL);

echo "<h1>🔍 Debug - Busca Incremental</h1>";
echo "<pre>";

// Exemplo de query SQL (substitua pela sua query real)
$sqlOriginal = "SELECT
	T0.\"DocEntry\",
	T0.\"DocNum\" AS \"NumDoc\",
	T0.\"ObjType\" AS \"ObjType\",
	T0.\"Serial\" AS \"NFe\",
	T0.\"CANCELED\",
	CASE 
		WHEN T0.\"CANCELED\" = 'Y' THEN 'Cancelado' 
		WHEN T0.\"CANCELED\" = 'C' THEN 'Cancelamento' 
		ELSE 'Não Cancelado' 
	END AS \"Cancelado\",
	T0.\"DocDate\",
	T0.\"CreateTS\"
FROM OINV T0 
INNER JOIN INV1 T1 ON T0.\"DocEntry\" = T1.\"DocEntry\" 
WHERE 
	T0.\"DocType\" = 'I' 
	AND T0.\"CANCELED\" = 'N' 
ORDER BY
	T0.\"DocDate\" DESC,
	T0.\"CreateTS\" DESC,
	T0.\"DocNum\" DESC";

echo "📝 SQL ORIGINAL:\n";
echo str_repeat("=", 80) . "\n";
echo $sqlOriginal . "\n";
echo str_repeat("=", 80) . "\n\n";

// Simular dados existentes no cache (exemplo)
$existingData = [
    ['DocEntry' => 20598, 'DocDate' => '2025-11-28', 'CreateTS' => 123456],
    ['DocEntry' => 20597, 'DocDate' => '2025-11-27', 'CreateTS' => 123450],
    ['DocEntry' => 20596, 'DocDate' => '2025-11-26', 'CreateTS' => 123440],
];

echo "📊 DADOS EXISTENTES NO CACHE (simulação):\n";
echo str_repeat("-", 80) . "\n";
foreach ($existingData as $row) {
    echo "DocEntry: {$row['DocEntry']}, DocDate: {$row['DocDate']}, CreateTS: {$row['CreateTS']}\n";
}
echo str_repeat("-", 80) . "\n\n";

// Usar Reflection para acessar método privado (apenas para debug)
$service = new DynamicQueryBuilderService();
$reflection = new ReflectionClass($service);

// Testar detecção de campo incremental
$detectMethod = $reflection->getMethod('detectIncrementalField');
$detectMethod->setAccessible(true);
$incrementalInfo = $detectMethod->invoke($service, $existingData);

echo "🔍 DETECÇÃO DE CAMPO INCREMENTAL:\n";
echo str_repeat("-", 80) . "\n";
if ($incrementalInfo) {
    echo "✅ Campo detectado: {$incrementalInfo['field']}\n";
    echo "   Tipo: {$incrementalInfo['type']}\n";
    if (isset($incrementalInfo['secondary'])) {
        echo "   Campo secundário: {$incrementalInfo['secondary']}\n";
    }
} else {
    echo "❌ Nenhum campo incremental detectado\n";
}
echo str_repeat("-", 80) . "\n\n";

// Testar detecção de alias
$detectAliasMethod = $reflection->getMethod('detectTableAliasForField');
$detectAliasMethod->setAccessible(true);
$field = $incrementalInfo['field'] ?? 'DocEntry';
$tableAlias = $detectAliasMethod->invoke($service, $sqlOriginal, $field);

echo "🏷️ DETECÇÃO DE ALIAS DA TABELA:\n";
echo str_repeat("-", 80) . "\n";
echo "Campo: {$field}\n";
echo "Alias detectado: " . ($tableAlias ?: 'nenhum') . "\n";
echo str_repeat("-", 80) . "\n\n";

// Testar otimização SQL
$optimizeMethod = $reflection->getMethod('optimizeSqlForIncremental');
$optimizeMethod->setAccessible(true);
$sqlOtimizada = $optimizeMethod->invoke($service, $sqlOriginal, $existingData);

echo "⚡ SQL OTIMIZADA PARA BUSCA INCREMENTAL:\n";
echo str_repeat("=", 80) . "\n";
echo $sqlOtimizada . "\n";
echo str_repeat("=", 80) . "\n\n";

// Comparar SQL original vs otimizada
echo "📊 COMPARAÇÃO:\n";
echo str_repeat("-", 80) . "\n";
echo "SQL Original (tamanho): " . strlen($sqlOriginal) . " caracteres\n";
echo "SQL Otimizada (tamanho): " . strlen($sqlOtimizada) . " caracteres\n";
echo "Diferença: " . (strlen($sqlOtimizada) - strlen($sqlOriginal)) . " caracteres\n";
echo str_repeat("-", 80) . "\n\n";

// Mostrar o que foi adicionado
$diff = str_replace($sqlOriginal, '', $sqlOtimizada);
if ($diff) {
    echo "➕ ADIÇÕES NA QUERY:\n";
    echo str_repeat("-", 80) . "\n";
    echo trim($diff) . "\n";
    echo str_repeat("-", 80) . "\n\n";
}

// Verificar se há WHERE duplicado
$whereCount = preg_match_all('/\bWHERE\b/i', $sqlOtimizada);
echo "🔍 VALIDAÇÃO:\n";
echo str_repeat("-", 80) . "\n";
echo "Quantidade de WHERE na query: {$whereCount}\n";
if ($whereCount > 1) {
    echo "❌ ERRO: Múltiplos WHERE detectados!\n";
} else {
    echo "✅ OK: Apenas um WHERE na query\n";
}
echo str_repeat("-", 80) . "\n\n";

// Explicação
echo "💡 EXPLICAÇÃO:\n";
echo str_repeat("=", 80) . "\n";
echo "1. O sistema detecta automaticamente o campo incremental (DocEntry, ID, ou data)\n";
echo "2. Encontra o maior valor desse campo nos dados existentes no cache\n";
echo "3. Detecta o alias da tabela na query SQL (ex: T0)\n";
echo "4. Adiciona uma cláusula WHERE/AND para filtrar apenas registros novos\n";
echo "5. A query otimizada busca apenas registros com valor maior que o último no cache\n";
echo "\n";
echo "⚠️ POSSÍVEIS PROBLEMAS:\n";
echo "- Se não há registros novos no banco, a API retornará 0 registros\n";
echo "- Se o alias da tabela não for detectado corretamente, pode gerar erro de ambiguidade\n";
echo "- Se a query já tem WHERE, deve usar AND ao invés de WHERE\n";
echo str_repeat("=", 80) . "\n";

echo "</pre>";





