<?php

declare(strict_types=1);

require __DIR__ . '/analyze_cost_files.php';

$path = 'C:/Users/rafael.oliveira/Desktop/Custos Original/Planilha_de_Custeio_Tiaraju_202605_Excel2016_REVISAO_COMPLETA.xlsx';
$sheets = readXlsxSheets($path);
$c = $sheets['CUSTEIO FABRIL'];
$col = 'F';

echo "=== Coluna F — linhas de custo e rateio ===\n";
foreach ([4, 6, 7, 8, 9, 10, 11, 12, 14, 22, 23, 1146, 1187, 1233, 2299, 2300, 2301, 2302, 2310, 2320, 2358] as $r) {
    $label = trim((string)($c[$r]['B'] ?? $c[$r]['A'] ?? ''));
    $val = $c[$r][$col] ?? '';
    echo sprintf("L%4d %-55s %s\n", $r, mb_substr($label, 0, 55), is_numeric($val) ? number_format((float)$val, 6, ',', '.') : $val);
}

// ER column row 10 fallback
$er10 = $c[10]['ER'] ?? $c[10]['er'] ?? null;
echo "\nER10 (fallback se F22=0): " . ($er10 ?? 'n/a') . "\n";
echo "F22 qty: " . ($c[22][$col] ?? '') . "\n";
