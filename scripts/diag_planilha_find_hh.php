<?php

declare(strict_types=1);

require __DIR__ . '/analyze_cost_files.php';

$path = 'C:/Users/rafael.oliveira/Desktop/Custos Original/Planilha_de_Custeio_Tiaraju_202605_Excel2016_REVISAO_COMPLETA.xlsx';
$erp = $argv[1] ?? '40500052';
$sheets = readXlsxSheets($path);
$form = $sheets['FORMPROD'];
$cust = $sheets['CUSTEIO FABRIL'];

$custCol = null;
foreach ($cust[17] ?? [] as $col => $val) {
    if (trim((string)$val) === $erp) {
        $custCol = $col;
        break;
    }
}
echo "CUSTEIO FABRIL L17: SKU em col {$custCol}\n";

$formCol = null;
foreach ($form as $r => $cells) {
    if ($r > 50) {
        break;
    }
    foreach ($cells as $col => $val) {
        if (trim((string)$val) === $erp && $col === $custCol) {
            echo "FORMPROD L{$r} col {$col} = {$val}\n";
            $formCol = $col;
        }
    }
}

// list all cols in row 17 FORMPROD with values
echo "\nFORMPROD L17 (amostra cols D-J):\n";
foreach (['D','E','F','G','H','I','J','K'] as $c) {
    $v = $form[17][$c] ?? '';
    if ($v !== '') {
        echo "  {$c}={$v}\n";
    }
}

// Search entire row 1096 for numeric times in col F
echo "\nCol F valores numéricos 0-50 nas linhas 1090-1200:\n";
for ($r = 1090; $r <= 1200; $r++) {
    $v = $form[$r]['F'] ?? null;
    if (is_numeric($v) && (float)$v > 0 && (float)$v < 50) {
        $label = trim((string)($form[$r]['C'] ?? $form[$r]['A'] ?? ''));
        echo sprintf("L%d %-35s F=%.6f\n", $r, mb_substr($label, 0, 35), (float)$v);
    }
}

// Maybe HH is in CUSTEIO FABRIL not FORMPROD - search rows around 1096
echo "\nCUSTEIO FABRIL col F linhas 1080-1200 (numéricos 0-50):\n";
for ($r = 1080; $r <= 1200; $r++) {
    $v = $cust[$r]['F'] ?? null;
    if (is_numeric($v) && (float)$v > 0 && (float)$v < 50) {
        $label = trim((string)($cust[$r]['A'] ?? $cust[$r]['B'] ?? ''));
        echo sprintf("L%d %-35s F=%.6f\n", $r, mb_substr($label, 0, 35), (float)$v);
    }
}
