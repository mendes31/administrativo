<?php
require __DIR__ . '/analyze_cost_files.php';
$s = readXlsxSheets('C:/Users/rafael.oliveira/Desktop/Custos Original/Planilha_de_Custeio_Tiaraju_202605_Excel2016_REVISAO_COMPLETA.xlsx');
$c = $s['CUSTEIO FABRIL'];
foreach ([21, 22, 23, 26, 1089, 1094, 1146, 1187, 1233, 1234, 2302] as $r) {
    $label = trim((string)($c[$r]['B'] ?? $c[$r]['A'] ?? ''));
    echo "L{$r} {$label} => " . ($c[$r]['F'] ?? '') . "\n";
}
