<?php

declare(strict_types=1);

require __DIR__ . '/analyze_cost_files.php';

$path = 'C:/Users/rafael.oliveira/Desktop/Custos Original/Planilha_de_Custeio_Tiaraju_202605_Excel2016_REVISAO_COMPLETA.xlsx';
$erp = $argv[1] ?? '40500052';

if (!is_readable($path)) {
    fwrite(STDERR, "Planilha não encontrada: $path\n");
    exit(1);
}

$sheets = readXlsxSheets($path);
$form = $sheets['FORMPROD'] ?? null;
$custeio = $sheets['CUSTEIO FABRIL'] ?? null;
if (!$form || !$custeio) {
    fwrite(STDERR, "Abas FORMPROD ou CUSTEIO FABRIL ausentes\n");
    exit(1);
}

// Localizar coluna do SKU na linha 17 do CUSTEIO FABRIL
$skuCol = null;
foreach ($custeio[17] ?? [] as $col => $val) {
    if (trim((string)$val) === $erp) {
        $skuCol = $col;
        break;
    }
}
if ($skuCol === null) {
    foreach ($custeio[17] ?? [] as $col => $val) {
        if (str_contains((string)$val, $erp)) {
            $skuCol = $col;
            break;
        }
    }
}
echo "SKU {$erp} coluna: " . ($skuCol ?? 'NÃO ENCONTRADO') . "\n\n";

if ($skuCol) {
    echo "=== CUSTEIO FABRIL (valores chave) ===\n";
    $keyRows = [
        4 => 'PREÇO VENDA LÍQ',
        6 => 'CVAR total',
        7 => 'CVAR MP',
        8 => 'CVAR MAE',
        9 => 'CFIX',
        11 => 'CUSTO PLENO',
        1554 => 'CVAR MP (alt)',
        2299 => 'CVAR MAE (alt)',
        2300 => 'CVAR total (alt)',
        2302 => 'CFIX (alt)',
    ];
    foreach ($keyRows as $r => $label) {
        $v = $custeio[$r][$skuCol] ?? $custeio[$r]['F'] ?? null;
        if ($v !== null && $v !== '') {
            echo sprintf("L%d %-25s: %s\n", $r, $label, is_numeric($v) ? number_format((float)$v, 4, ',', '.') : $v);
        }
    }
}

// FORMPROD: encontrar coluna do produto (geralmente linha 2 ou 3 com código SAP)
$formCol = null;
foreach ($form as $rowNum => $cells) {
    foreach ($cells as $col => $val) {
        if (trim((string)$val) === $erp) {
            $formCol = $col;
            echo "\nFORMPROD SKU na linha {$rowNum} col {$col}\n";
            break 2;
        }
    }
}

if ($formCol) {
    echo "\n=== FORMPROD — operações/tempos (linhas 1098-1143) ===\n";
    $totalHh = 0.0;
    for ($r = 1098; $r <= 1143; $r++) {
        if (!isset($form[$r])) {
            continue;
        }
        $label = trim((string)($form[$r]['A'] ?? $form[$r]['B'] ?? ''));
        $time = $form[$r][$formCol] ?? null;
        $colab = $form[$r][chr(ord($formCol) + 1)] ?? null; // guess
        if ($time === null || $time === '' || !is_numeric($time)) {
            continue;
        }
        $t = (float)$time;
        if ($t <= 0) {
            continue;
        }
        echo sprintf("L%d %-40s tempo=%.4f\n", $r, mb_substr($label, 0, 40), $t);
        $totalHh += $t;
    }
    echo "\nSoma tempos (sem × colab): " . number_format($totalHh, 4, ',', '.') . " h\n";

    echo "\n=== FORMPROD — HM (1150-1184) ===\n";
    $totalHm = 0.0;
    for ($r = 1150; $r <= 1184; $r++) {
        if (!isset($form[$r])) {
            continue;
        }
        $label = trim((string)($form[$r]['A'] ?? $form[$r]['B'] ?? ''));
        $hm = $form[$r][$formCol] ?? null;
        if ($hm === null || $hm === '' || !is_numeric($hm)) {
            continue;
        }
        $h = (float)$hm;
        if ($h <= 0) {
            continue;
        }
        echo sprintf("L%d %-40s HM=%.4f\n", $r, mb_substr($label, 0, 40), $h);
        $totalHm += $h;
    }
    echo "\nSoma HM: " . number_format($totalHm, 4, ',', '.') . " h\n";
}

// Dump linha 1094-1145 col A-F + sku col for structure
if ($formCol && isset($form[1094])) {
    echo "\n=== Amostra FORMPROD 1094-1110 (cols A,B,{$formCol}) ===\n";
    for ($r = 1094; $r <= 1110; $r++) {
        $a = $form[$r]['A'] ?? '';
        $b = $form[$r]['B'] ?? '';
        $v = $form[$r][$formCol] ?? '';
        echo "L{$r}: A={$a} | B={$b} | {$formCol}={$v}\n";
    }
}
