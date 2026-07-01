<?php

declare(strict_types=1);

require __DIR__ . '/analyze_cost_files.php';

$path = 'C:/Users/rafael.oliveira/Desktop/Custos Original/Planilha_de_Custeio_Tiaraju_202605_Excel2016_REVISAO_COMPLETA.xlsx';
$erp = $argv[1] ?? '40500052';
$sheets = readXlsxSheets($path);
$form = $sheets['FORMPROD'];

echo "=== Busca coluna SKU em FORMPROD (linhas 1-25) ===\n";
$formCol = null;
for ($r = 1; $r <= 25; $r++) {
    foreach ($form[$r] ?? [] as $col => $val) {
        if (trim((string)$val) === $erp) {
            echo "Encontrado L{$r} col {$col}\n";
            $formCol = $col;
        }
    }
}

if ($formCol === null) {
    // CUSTEIO FABRIL L17 col F
    $formCol = 'F';
    echo "Usando col F (CUSTEIO FABRIL L17)\n";
}

echo "\n=== FORMPROD L1094-1145 col A,B,C,{$formCol} ===\n";
for ($r = 1094; $r <= 1145; $r++) {
    $cells = $form[$r] ?? [];
    $a = $cells['A'] ?? '';
    $b = $cells['B'] ?? '';
    $c = $cells['C'] ?? '';
    $f = $cells[$formCol] ?? '';
    if ($a === '' && $b === '' && $f === '') {
        continue;
    }
    echo sprintf("L%d: A=%s | B=%s | C=%s | %s=%s\n", $r, $a, $b, $c, $formCol, $f);
}

echo "\n=== HH planilha (tempo × colab se col seguinte numérica) ===\n";
$hh = 0.0;
$nextCol = chr(ord($formCol) + 1);
for ($r = 1098; $r <= 1143; $r++) {
    $cells = $form[$r] ?? [];
    $time = $cells[$formCol] ?? null;
    $colab = $cells[$nextCol] ?? 1;
    if (!is_numeric($time) || (float)$time <= 0 || (float)$time > 100) {
        continue;
    }
    $t = (float)$time;
    $q = is_numeric($colab) && (float)$colab > 0 && (float)$colab < 20 ? (float)$colab : 1.0;
    $lineHh = $t * $q;
    $hh += $lineHh;
    $label = trim((string)($cells['C'] ?? $cells['B'] ?? $cells['A'] ?? ''));
    echo sprintf("L%d %-30s t=%.4f × col=%.1f => %.4f h\n", $r, mb_substr($label, 0, 30), $t, $q, $lineHh);
}
echo "TOTAL HH/lote: " . number_format($hh, 4, ',', '.') . " h\n";

echo "\n=== HM planilha (1150-1184) ===\n";
$hm = 0.0;
for ($r = 1150; $r <= 1184; $r++) {
    $cells = $form[$r] ?? [];
    $val = $cells[$formCol] ?? null;
    if (!is_numeric($val) || (float)$val <= 0 || (float)$val > 100) {
        continue;
    }
    $h = (float)$val;
    $hm += $h;
    $label = trim((string)($cells['C'] ?? $cells['B'] ?? $cells['A'] ?? ''));
    echo sprintf("L%d %-30s HM=%.4f\n", $r, mb_substr($label, 0, 30), $h);
}
echo "TOTAL HM/lote: " . number_format($hm, 4, ',', '.') . " h\n";
