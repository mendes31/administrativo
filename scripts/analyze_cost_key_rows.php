<?php
declare(strict_types=1);

require __DIR__ . '/analyze_cost_files.php';

$xlsxPath = 'S:/Temporaria/CONSULTORIA PRECIFICAÇÃO/Planilha de Custeio Tiaraju 202605.xlsx';
$sheets = readXlsxSheets($xlsxPath);
$main = $sheets['CUSTEIO FABRIL'] ?? [];

$keyRows = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,1094,1146,1187,1230,1233,1554,2296,2299,2300,2301,2307,2309,2320,2336,2358];
$labels = ['B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

$out = "=== LINHAS-CHAVE CUSTEIO FABRIL ===\n\n";
foreach ($keyRows as $r) {
    if (!isset($main[$r])) continue;
    $cells = $main[$r];
    $label = $cells['B'] ?? $cells['A'] ?? '';
    $out .= "LINHA $r: $label\n";
    foreach ($cells as $col => $val) {
        if (in_array($col, ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC'], true)) {
            $v = mb_substr((string)$val, 0, 200);
            $out .= "  $col$r = $v\n";
        }
    }
    $out .= "\n";
}

// Scan rows where col B contains CRITÉRIO or CVAR or CFIX or MARGEM
$out .= "=== ROTULOS COLUNA B (rateios e totais) ===\n";
foreach ($main as $r => $cells) {
    $b = mb_strtoupper((string)($cells['B'] ?? ''), 'UTF-8');
    if ($b === '') continue;
    if (str_contains($b, 'CRITÉRIO') || str_contains($b, 'CRITERIO')
        || str_contains($b, 'CVAR') || str_contains($b, 'CFIX')
        || str_contains($b, 'MARGEM') || str_contains($b, 'CUSTO FIXO')
        || str_contains($b, 'CUSTO VARI') || str_contains($b, 'PREÇO')
        || str_contains($b, 'EFICI') || str_contains($b, 'HH') || str_contains($b, 'HM')
        || str_contains($b, 'ENERGIA') || str_contains($b, 'FOLHA')
        || str_contains($b, 'COMPLEX') || str_contains($b, 'HVAC')) {
        $out .= "L$r: " . ($cells['B'] ?? '') . "\n";
    }
}

file_put_contents(sys_get_temp_dir() . '/inv_cost_analysis_output/custeio_key_rows.txt', $out);
echo $out;
