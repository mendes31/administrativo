<?php
/**
 * Script para comparar query direta no banco vs query incremental do sistema
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

echo "<h1>🔍 Comparação: Query Direta vs Query Incremental</h1>";
echo "<pre>";

// Query SQL original (exemplo baseado na imagem)
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
	CASE 
		WHEN T0.\"DocStatus\" = 'C' THEN 'Fechado' 
		ELSE 'Aberto' 
	END AS \"DocStatus\",
	CASE T0.\"ObjType\" 
		WHEN '13' THEN 'Fatura' 
		WHEN '14' THEN 'NC' 
	END AS \"TipoDocumento\",
	T0.\"Confirmed\" AS \"Confirmada\",
	T0.\"TransId\" AS \"TransaçãoID\",
	TO_VARCHAR(T0.\"DocDate\", 'DD/MM/YYYY') AS \"DataCriacao\",
	T0.\"CreateTS\" AS \"HoraCriação\",
	TO_VARCHAR(
        ADD_SECONDS(TO_TIMESTAMP('1970-01-01'), 
        FLOOR(T0.\"CreateTS\"/10000)*3600 + FLOOR(MOD(T0.\"CreateTS\",10000)/100)*60 + MOD(T0.\"CreateTS\",100)),
        'HH24:MI:SS'
    ) AS \"Hora\",
	EXTRACT(YEAR FROM T0.\"DocDate\")  AS \"AnoCriação\",
    EXTRACT(MONTH FROM T0.\"DocDate\") AS \"MesCriação\",
	EXTRACT(DAY FROM T0.\"DocDate\") AS \"DiaCriação\",
	T0.\"BPLId\" AS \"FilialID\",
	T0.\"BPLName\" AS \"Filial\",
	T0.\"CardCode\" AS \"cdPN\",
	T0.\"CardName\" AS \"nomePN\",
	T0.\"Address\" AS \"EndereçoCliente\",
	T7.\"GroupName\" AS \"nomeGrupoPN\",
	T2.\"SlpName\" AS \"nomeVendedor\",
	T1.\"ItemCode\" AS \"cdItem\",
	T1.\"Dscription\" AS \"nomeItem\",
	T5.\"ItmsGrpNam\" AS \"nomeGrupoItem\",
	T1.\"Quantity\" AS \"Qtde\",
	T1.\"Currency\" AS \"Moeda\",
	T1.\"StockPrice\" AS \"Custo_Unitário\",
	(T1.\"StockPrice\" * T1.\"Quantity\") AS \"Custo_Total_Item\",
	COALESCE(T10.\"TotalImpostos\", 0) AS \"Total Impostos\",
	((T1.\"StockPrice\" * T1.\"Quantity\") + COALESCE(T10.\"TotalImpostos\", 0)) AS \"Custo_+_Impostos\",
	T1.\"PriceBefDi\" AS \"Preco Unitario\",
	COALESCE((T1.\"DiscPrcnt\" / 100), 0) AS \"Desconto%\",
	T1.\"Price\" AS \"Preco Final\",
	(T1.\"PriceBefDi\" * T1.\"Quantity\") AS \"Total s/ Desc\",
	T1.\"LineTotal\" AS \"Total c/ Desc\",
	SUM(T1.\"LineTotal\") OVER (PARTITION BY T0.\"DocNum\") AS \"Total Itens Documento\",
	CASE 
		WHEN (SUM(T1.\"LineTotal\") OVER (PARTITION BY T0.\"DocNum\")) > 0 THEN ROUND((T1.\"LineTotal\" / (SUM(T1.\"LineTotal\") OVER (PARTITION BY T0.\"DocNum\"))),4) 
		ELSE 0 
	END AS \"%Linha\",
	COALESCE(T8.\"LineTotal\", 0) AS \"Despesa add Rodapé\",
	COALESCE(T0.\"DiscSum\", 0.00) AS \"Desc Rodapé\",
	CASE 
		WHEN (SUM(T1.\"LineTotal\") OVER (PARTITION BY T0.\"DocNum\")) > 0 
		THEN ((T1.\"LineTotal\" / (SUM(T1.\"LineTotal\") OVER (PARTITION BY T0.\"DocNum\"))) * T0.\"DiscSum\") 
		ELSE 0 
	END AS \"Distribuicao Desc Rodapé\",
	CASE 
		WHEN (SUM(T1.\"LineTotal\") OVER (PARTITION BY T0.\"DocNum\")) > 0 THEN (T1.\"LineTotal\" - ((T1.\"LineTotal\" / (SUM(T1.\"LineTotal\") OVER (PARTITION BY T0.\"DocNum\"))) * T0.\"DiscSum\" )) 
		ELSE 0 
	END AS \"Valor final\",
 	T1.\"Usage\" AS \"cdUtilizacao\",
	T3.\"Usage\" AS \"Utilizacao\",
	CASE 
		WHEN T9.\"StateS\" = '' THEN T9.\"StateB\" 
		ELSE T9.\"StateS\" 
	END AS \"Estado\",
 	CASE 
 		WHEN T9.\"CountryS\" = '' THEN T9.\"CountryB\" 
		ELSE T9.\"CountryS\" 
	END AS \"País\" 
FROM OINV T0 
INNER JOIN INV1 T1 ON T0.\"DocEntry\" = T1.\"DocEntry\" 
INNER JOIN OSLP T2 ON T0.\"SlpCode\" = T2.\"SlpCode\" 
INNER JOIN OUSG T3 ON T1.\"Usage\" = T3.\"ID\" 
INNER JOIN OITM T4 ON T1.\"ItemCode\" = T4.\"ItemCode\" 
INNER JOIN OITB T5 ON T4.\"ItmsGrpCod\" = T5.\"ItmsGrpCod\" 
INNER JOIN OCRD T6 ON T0.\"CardCode\" = T6.\"CardCode\" 
INNER JOIN OCRG T7 ON T6.\"GroupCode\" = T7.\"GroupCode\" 
LEFT JOIN INV3 T8 ON T0.\"DocEntry\" = T8.\"DocEntry\" 
INNER JOIN INV12 T9 ON T0.\"DocEntry\" = T9.\"DocEntry\" 
LEFT JOIN 
( SELECT
	\"DocEntry\",
	\"LineNum\",
	SUM(\"TaxSum\") AS \"TotalImpostos\" 
FROM INV4 
GROUP BY 
	\"DocEntry\",
	\"LineNum\" ) T10 ON T1.\"DocEntry\" = T10.\"DocEntry\" 
	AND T1.\"LineNum\" = T10.\"LineNum\" 
WHERE 
	T0.\"DocType\" = 'I' 
	AND (T4.\"ItmsGrpCod\" = '104' OR T4.\"ItmsGrpCod\" = '106') 
	AND T0.\"CANCELED\" = 'N' 
ORDER BY
	T0.\"DocDate\" DESC,
	T0.\"CreateTS\" DESC,
	T0.\"DocNum\" DESC,
	T1.\"LineNum\"";

echo "📝 QUERY SQL ORIGINAL:\n";
echo str_repeat("=", 100) . "\n";
echo $sqlOriginal . "\n";
echo str_repeat("=", 100) . "\n\n";

// Simular dados existentes no cache (baseado na imagem: DocEntry 20598 é o mais recente no sistema)
$existingData = [
    ['DocEntry' => 20598, 'DocDate' => '28/11/2025', 'CreateTS' => 154722],
    ['DocEntry' => 20597, 'DocDate' => '28/11/2025', 'CreateTS' => 154636],
    ['DocEntry' => 20596, 'DocDate' => '28/11/2025', 'CreateTS' => 154530],
];

echo "📊 DADOS EXISTENTES NO CACHE (simulação baseada na imagem):\n";
echo str_repeat("-", 100) . "\n";
foreach ($existingData as $row) {
    echo "DocEntry: {$row['DocEntry']}, DataCriacao: {$row['DocDate']}, HoraCriacao: {$row['CreateTS']}\n";
}
echo str_repeat("-", 100) . "\n\n";

// Usar Reflection para acessar método privado
$service = new DynamicQueryBuilderService();
$reflection = new ReflectionClass($service);

// Testar otimização SQL
$optimizeMethod = $reflection->getMethod('optimizeSqlForIncremental');
$optimizeMethod->setAccessible(true);
$sqlOtimizada = $optimizeMethod->invoke($service, $sqlOriginal, $existingData);

echo "⚡ QUERY SQL OTIMIZADA (INCREMENTAL):\n";
echo str_repeat("=", 100) . "\n";
echo $sqlOtimizada . "\n";
echo str_repeat("=", 100) . "\n\n";

// Comparar as queries
echo "📊 COMPARAÇÃO DETALHADA:\n";
echo str_repeat("-", 100) . "\n";

// Extrair a cláusula WHERE de cada query
preg_match('/WHERE\s+(.*?)(?=\s+ORDER\s+BY|\s*$)/is', $sqlOriginal, $matchesOriginal);
preg_match('/WHERE\s+(.*?)(?=\s+ORDER\s+BY|\s*$)/is', $sqlOtimizada, $matchesOtimizada);

$whereOriginal = $matchesOriginal[1] ?? 'NÃO ENCONTRADO';
$whereOtimizada = $matchesOtimizada[1] ?? 'NÃO ENCONTRADO';

echo "WHERE da Query Original:\n";
echo "  " . trim($whereOriginal) . "\n\n";

echo "WHERE da Query Otimizada:\n";
echo "  " . trim($whereOtimizada) . "\n\n";

// Verificar diferenças
$diff = str_replace($whereOriginal, '', $whereOtimizada);
$diff = trim($diff);

echo "➕ DIFERENÇA (o que foi adicionado):\n";
echo "  " . ($diff ?: 'NENHUMA DIFERENÇA DETECTADA') . "\n\n";

// Verificar se a cláusula incremental está presente
if (stripos($sqlOtimizada, 'DocEntry') !== false && stripos($sqlOtimizada, '> 20598') !== false) {
    echo "✅ Cláusula incremental detectada: T0.\"DocEntry\" > 20598\n";
} else {
    echo "❌ Cláusula incremental NÃO detectada na query otimizada!\n";
}

echo str_repeat("-", 100) . "\n\n";

// Mostrar o que deveria retornar
echo "💡 O QUE DEVERIA ACONTECER:\n";
echo str_repeat("=", 100) . "\n";
echo "1. Query Original: Retorna TODOS os registros (incluindo DocEntry 20598, 20597, etc.)\n";
echo "2. Query Incremental: Deve retornar APENAS registros com DocEntry > 20598\n";
echo "   - Deve retornar: DocEntry 20599, 20600, 20601, 20602, ... até 20606\n";
echo "   - NÃO deve retornar: DocEntry 20598, 20597, 20596 (já estão no cache)\n";
echo "\n";
echo "📋 Baseado na imagem do banco:\n";
echo "   - Último DocEntry no cache: 20598\n";
echo "   - DocEntry mais recente no banco: 20606\n";
echo "   - Registros novos esperados: 20606, 20605, 20604, 20603, 20602, 20601, 20600, 20599\n";
echo "   - Total de novos registros esperados: 8 registros\n";
echo str_repeat("=", 100) . "\n\n";

// Verificar problemas potenciais
echo "🔍 VERIFICAÇÃO DE PROBLEMAS:\n";
echo str_repeat("-", 100) . "\n";

// 1. Verificar se há WHERE duplicado
$whereCount = preg_match_all('/\bWHERE\b/i', $sqlOtimizada);
if ($whereCount > 1) {
    echo "❌ PROBLEMA 1: Múltiplos WHERE detectados ({$whereCount})\n";
} else {
    echo "✅ OK: Apenas um WHERE na query\n";
}

// 2. Verificar se o alias está correto
if (stripos($sqlOtimizada, 'T0."DocEntry"') !== false) {
    echo "✅ OK: Alias T0 detectado corretamente\n";
} elseif (stripos($sqlOtimizada, '"DocEntry"') !== false) {
    echo "⚠️ AVISO: DocEntry sem alias (pode causar ambiguidade)\n";
} else {
    echo "❌ PROBLEMA 2: DocEntry não encontrado na query otimizada\n";
}

// 3. Verificar se a comparação está correta
if (stripos($sqlOtimizada, '> 20598') !== false || stripos($sqlOtimizada, '>20598') !== false) {
    echo "✅ OK: Comparação '> 20598' encontrada\n";
} else {
    echo "❌ PROBLEMA 3: Comparação '> 20598' NÃO encontrada\n";
    // Tentar encontrar qualquer comparação com DocEntry
    if (preg_match('/DocEntry\s*[><=]+\s*(\d+)/i', $sqlOtimizada, $matches)) {
        echo "   Encontrado: DocEntry " . $matches[0] . "\n";
    }
}

// 4. Verificar se há ORDER BY que pode interferir
if (stripos($sqlOtimizada, 'ORDER BY') !== false) {
    echo "✅ OK: ORDER BY presente (importante para ordenação correta)\n";
} else {
    echo "⚠️ AVISO: ORDER BY não encontrado\n";
}

echo str_repeat("-", 100) . "\n\n";

// Mostrar query que deveria ser executada no banco
echo "📝 QUERY QUE DEVERIA SER EXECUTADA NO BANCO (para comparar):\n";
echo str_repeat("=", 100) . "\n";
$queryBanco = str_replace(
    "AND T0.\"CANCELED\" = 'N'",
    "AND T0.\"CANCELED\" = 'N' AND T0.\"DocEntry\" > 20598",
    $sqlOriginal
);
echo $queryBanco . "\n";
echo str_repeat("=", 100) . "\n\n";

// Comparar com a query otimizada
if (trim($sqlOtimizada) === trim($queryBanco)) {
    echo "✅ SUCESSO: Query otimizada é IDÊNTICA à query esperada!\n";
} else {
    echo "❌ DIFERENÇA: Query otimizada é DIFERENTE da query esperada!\n";
    echo "\nDiferenças encontradas:\n";
    
    // Comparar linha por linha
    $linesOtimizada = explode("\n", $sqlOtimizada);
    $linesBanco = explode("\n", $queryBanco);
    
    $maxLines = max(count($linesOtimizada), count($linesBanco));
    for ($i = 0; $i < $maxLines; $i++) {
        $lineOtimizada = trim($linesOtimizada[$i] ?? '');
        $lineBanco = trim($linesBanco[$i] ?? '');
        
        if ($lineOtimizada !== $lineBanco) {
            echo "Linha " . ($i + 1) . ":\n";
            echo "  Otimizada: " . substr($lineOtimizada, 0, 100) . "\n";
            echo "  Esperada:  " . substr($lineBanco, 0, 100) . "\n";
        }
    }
}

echo "</pre>";




