<?php

declare(strict_types=1);

require __DIR__ . '/analyze_cost_files.php';

$path = 'C:/Users/rafael.oliveira/Desktop/Custos Original/Planilha_de_Custeio_Tiaraju_202605_Excel2016_REVISAO_COMPLETA.xlsx';
$erp = $argv[1] ?? '41000052';

if (!is_readable($path)) {
    fwrite(STDERR, "Planilha não encontrada: $path\n");
    exit(1);
}

$sheets = readXlsxSheets($path);
$form = $sheets['FORMPROD'] ?? null;
if (!$form) {
    exit(1);
}

$formCol = null;
foreach ($form as $rowNum => $cells) {
    foreach ($cells as $col => $val) {
        if (trim((string)$val) === $erp) {
            $formCol = $col;
            break 2;
        }
    }
}
if ($formCol === null) {
    fwrite(STDERR, "SKU não encontrado em FORMPROD\n");
    exit(1);
}

echo "=== FORMPROD BOM/estrutura — {$erp} col {$formCol} (linhas 1-400) ===\n";
$currentPi = '';
for ($r = 1; $r <= 400; $r++) {
    if (!isset($form[$r])) {
        continue;
    }
    $a = trim((string)($form[$r]['A'] ?? ''));
    $b = trim((string)($form[$r]['B'] ?? ''));
    $c = trim((string)($form[$r]['C'] ?? ''));
    $qty = $form[$r][$formCol] ?? '';
    if ($qty === '' || $qty === null) {
        continue;
    }
    if (!is_numeric($qty) && trim((string)$qty) === '') {
        continue;
    }

    $code = $b !== '' ? $b : $a;
    if ($code === '' && $c === '') {
        continue;
    }

    // PI headers in planilha often have description in C
    $desc = $c !== '' ? $c : '';
    $isPiHeader = preg_match('/^61\d{6}$/', $code) || str_contains(mb_strtoupper($desc), 'OLEO') || str_contains(mb_strtoupper($desc), 'GELATINA');
    if (preg_match('/^(61|60)\d{5,}$/', $code)) {
        $currentPi = $code . ($desc !== '' ? " — {$desc}" : '');
        echo "\n--- PI/BLOCO: {$currentPi} ---\n";
    }

    $qtyF = is_numeric($qty) ? (float)$qty : $qty;
    $ctx = $currentPi !== '' ? " [em {$currentPi}]" : '';
    echo sprintf("L%4d %-12s %-45s qty=%s%s\n", $r, mb_substr($code, 0, 12), mb_substr($desc !== '' ? $desc : $a, 0, 45), $qtyF, $ctx);
}

echo "\n=== FORMPROD tempos HH (1094-1145) — {$erp} ===\n";
for ($r = 1094; $r <= 1145; $r++) {
    if (!isset($form[$r])) {
        continue;
    }
    $label = trim((string)($form[$r]['A'] ?? $form[$r]['B'] ?? ''));
    $time = $form[$r][$formCol] ?? null;
    if ($time === null || $time === '' || !is_numeric($time)) {
        continue;
    }
    $t = (float)$time;
    if ($t <= 0) {
        continue;
    }
    $colabCol = chr(ord($formCol) + 1);
    $colab = trim((string)($form[$r][$colabCol] ?? ''));
    echo sprintf("L%d %-42s %.4f h  %s\n", $r, mb_substr($label, 0, 42), $t, $colab !== '' ? "MO: {$colab}" : '');
}
