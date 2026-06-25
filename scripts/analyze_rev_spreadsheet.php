<?php
/**
 * Análise local da planilha REVISAO_COMPLETA — uso interno, não expor na internet.
 */
declare(strict_types=1);

require __DIR__ . '/analyze_cost_files.php';

$path = 'C:/Users/rafael.oliveira/Desktop/Custos Original/Planilha_de_Custeio_Tiaraju_202605_Excel2016_REVISAO_COMPLETA.xlsx';
if (!is_readable($path)) {
    fwrite(STDERR, "Arquivo não encontrado: $path\n");
    exit(1);
}

echo "Arquivo: $path\n";
echo "Tamanho: " . number_format(filesize($path) / 1024 / 1024, 2) . " MB\n\n";

$sheets = readXlsxSheets($path);
echo "=== ABAS (" . count($sheets) . ") ===\n";
foreach (array_keys($sheets) as $i => $name) {
    $rows = count($sheets[$name]);
    echo ($i + 1) . ". $name ($rows linhas com dados)\n";
}

$keySheets = [
    'CUSTEIO FABRIL',
    'FORMPROD',
    'Pasta 3 - QTDADES. PRODUZIDAS',
    'Pasta 8 - DRE Balancete',
    'Pasta 11 - PREÇOS DE VENDA',
    'DASHBOARD',
];

echo "\n=== LINHAS-CHAVE (CUSTEIO FABRIL) ===\n";
$main = $sheets['CUSTEIO FABRIL'] ?? null;
if ($main) {
    $keyRows = [5, 7, 8, 9, 10, 12, 14, 17, 20, 21, 22, 23, 26, 1094, 1554, 2299, 2300, 2302, 2511];
    foreach ($keyRows as $r) {
        if (!isset($main[$r])) {
            continue;
        }
        $cells = $main[$r];
        ksort($cells);
        $sample = [];
        $n = 0;
        foreach ($cells as $col => $val) {
            if ($col === 'A' || $col === 'B' || $col === 'C' || $col === 'D' || $col === 'E' || $col === 'F') {
                $sample[] = "$col=$val";
            }
            if (++$n >= 8) {
                break;
            }
        }
        echo "L$r: " . implode(' | ', $sample) . "\n";
    }
    // count product columns (row 17 with SAP codes starting with 4)
    $row17 = $main[17] ?? [];
    $skuCols = 0;
    foreach ($row17 as $col => $val) {
        if ($col === 'A' || $col === 'B' || $col === 'C') {
            continue;
        }
        if (preg_match('/^4\d+/', (string)$val)) {
            $skuCols++;
        }
    }
    echo "\nSKUs detectados (linha 17): ~$skuCols colunas\n";
}

echo "\n=== PREVIEW PASTAS ===\n";
foreach ($keySheets as $name) {
    if (!isset($sheets[$name])) {
        echo "\n--- $name: NÃO ENCONTRADA ---\n";
        continue;
    }
    echo "\n--- $name (primeiras 15 linhas) ---\n";
    echo dumpSheetPreview($sheets[$name], 15) . "\n";
}
