<?php

declare(strict_types=1);

require __DIR__ . '/analyze_cost_files.php';

$path = 'C:/Users/rafael.oliveira/Desktop/Custos Original/Planilha_de_Custeio_Tiaraju_202605_Excel2016_REVISAO_COMPLETA.xlsx';
$erp = $argv[1] ?? '40500052';
$sheets = readXlsxSheets($path);
$cust = $sheets['CUSTEIO FABRIL'];
$col = 'F';

echo "=== CUSTEIO FABRIL col {$col} — SKU {$erp} (linhas 1070-1210) ===\n";
for ($r = 1070; $r <= 1210; $r++) {
    if (!isset($cust[$r])) {
        continue;
    }
    $cells = $cust[$r];
    $a = trim((string)($cells['A'] ?? ''));
    $b = trim((string)($cells['B'] ?? ''));
    $f = $cells[$col] ?? '';
    if ($a === '' && $b === '' && ($f === '' || $f === null)) {
        continue;
    }
    $label = $b !== '' ? $b : $a;
    $fv = is_numeric($f) ? number_format((float)$f, 6, ',', '.') : (string)$f;
    echo sprintf("L%4d %-55s | %s\n", $r, mb_substr($label, 0, 55), $fv);
}
