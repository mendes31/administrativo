<?php
/**
 * Mapeamento conta DRE → critério conforme aba CUSTEIO FABRIL (linhas 2317-2417).
 * Uso: php scripts/extract_dre_criterion_map.php
 */
declare(strict_types=1);

require __DIR__ . '/analyze_cost_files.php';

$path = 'C:/Users/rafael.oliveira/Desktop/Custos Original/Planilha_de_Custeio_Tiaraju_202605_Excel2016_REVISAO_COMPLETA.xlsx';
if (!is_readable($path)) {
    fwrite(STDERR, "Arquivo não encontrado: $path\n");
    exit(1);
}

$sheets = readXlsxSheets($path);
$main = $sheets['CUSTEIO FABRIL'] ?? [];

$criterionNames = [
    1 => '1 — Qty produzida',
    2 => '2 — Homem-hora (HH)',
    3 => '3 — Horas-máquina (HM)',
    4 => '4 — Complexidade',
    5 => '5 — Nº matérias-primas',
    6 => '6 — Complexidade × análises',
    7 => '7 — Energia (kWh)',
    8 => '8 — HVAC (CM/Prob/Outro)',
];

function parseCriterionFromCell(string $text): ?int
{
    $t = mb_strtolower(trim($text), 'UTF-8');
    if ($t === '') {
        return null;
    }
    if (preg_match('/crit[eé]rio\s*(\d)/u', $t, $m)) {
        return (int)$m[1];
    }
    if (preg_match('/^(\d)\s*[-—]/u', $t, $m)) {
        return (int)$m[1];
    }

    return null;
}

/** @return array{code: string, description: string}|null */
function parseAccountLabel(string $label): ?array
{
    $label = trim(str_replace("\n", ' ', $label));
    if ($label === '' || str_starts_with($label, '**') || str_starts_with($label, 'DISTRIBUI')) {
        return null;
    }
    // Skip area names from Pasta 9 block (no numeric code prefix)
    if (!preg_match('/^(\d{1,4})\s*[-–]\s*(.+)$/u', $label, $m)
        && !preg_match('/^(\d{1,4})\s+(.+)$/u', $label, $m)
        && !preg_match('/^(\d{3,4})-\s*(.+)$/u', $label, $m)) {
        // 818- Remuneração style
        if (preg_match('/^(\d{3,4})-\s*(.+)$/u', $label, $m2)) {
            return ['code' => $m2[1], 'description' => trim($m2[2])];
        }
        return null;
    }

    return ['code' => $m[1], 'description' => trim($m[2])];
}

$mapping = [];

// Block 1: Folha detail 2317-2331 (critério implícito 2 após Pasta 9 → Produção; simplificado: 2 para todas contas folha)
$folhaCodes = ['818', '819', '820', '823', '824', '825', '826', '828', '829', '830', '831', '834'];
foreach ($folhaCodes as $code) {
    $mapping[$code] = null; // placeholder
}

for ($r = 2315; $r <= 2417; $r++) {
    if (!isset($main[$r])) {
        continue;
    }
    $cells = $main[$r];
    $label = trim(str_replace("\n", ' ', (string)($cells['B'] ?? '')));
    if ($label === '') {
        continue;
    }

    $parsed = parseAccountLabel($label);
    if ($parsed === null) {
        continue;
    }

    $code = $parsed['code'];
    $desc = $parsed['description'];
    $criterion = parseCriterionFromCell((string)($cells['E'] ?? ''));
    $note = '';

    if ($criterion === null) {
        // Folha block 2317-2331: planilha rateia via Pasta 9; no sistema → critério 2 (HH)
        if ($r >= 2317 && $r <= 2331) {
            $criterion = 2;
            $note = 'Folha: após Pasta 9 a maior parte vai para Produção (HH). Sistema ainda sem Pasta 9 → use crit. 2.';
        } elseif ($r >= 2358 && $r <= 2417) {
            // Despesas adm sem col E: na planilha o rateio é calculado pela % do total (col D) × matriz — equivale a mix de critérios
            // Heurística por conta:
            $criterion = heuristicCriterion($code, $desc);
            $note = 'Adm: rateio na planilha via % total DRE; critério dominante sugerido.';
        } elseif (preg_match('/^81[6-7]/', $code) || $label === '816- DESPESAS ADMINISTRATIVAS') {
            continue; // totais
        } else {
            $criterion = heuristicCriterion($code, $desc);
            $note = 'Heurística';
        }
    } else {
        $note = 'Coluna E CUSTEIO FABRIL R' . $r;
    }

    // Overrides conhecidos da planilha (bloco adm 2358+)
    $override = spreadsheetOverride($code, $desc, $r);
    if ($override !== null) {
        $criterion = $override['criterion'];
        $note = $override['note'];
    }

    $mapping[$code] = [
        'code' => $code,
        'description' => $desc,
        'criterion' => $criterion,
        'criterion_label' => $criterionNames[$criterion] ?? (string)$criterion,
        'cf_row' => $r,
        'note' => $note,
    ];
}

function heuristicCriterion(string $code, string $desc): int
{
    $t = mb_strtolower($code . ' ' . $desc, 'UTF-8');
    if (preg_match('/sal[aá]rio|encargo|fgts|previd|benef[ií]cio|f[eé]rias|13\s*sal|estagi|pr[oó].*labore|remunera|pat\s|vale\s*transp|odontol|assist.*m[eé]dica|pessoal|cursos?\s*e\s*treinamento/i', $t)) {
        return 2;
    }
    if (preg_match('/manuten.*m[aá]q|manuten.*equip|deprecia/i', $t)) {
        return 3;
    }
    if (preg_match('/energia\s*el[eé]trica/i', $t)) {
        return 7;
    }
    if (preg_match('/an[aá]lise|pesquisa/i', $t)) {
        return 6;
    }

    return 1;
}

/** @return array{criterion: int, note: string}|null */
function spreadsheetOverride(string $code, string $desc, int $row): ?array
{
    $overrides = [
        '29' => ['criterion' => 7, 'note' => 'EE: na planilha redistribui 7+3+8; use 7 ou divida valor no CSV'],
        '872' => ['criterion' => 6, 'note' => 'Análises: planilha redistribui para CQ/P&D/DA → crit. 6'],
        '877' => ['criterion' => 3, 'note' => 'Manutenção máq./equip. → HM'],
        '882' => ['criterion' => 3, 'note' => 'Manutenção equipamentos → HM'],
        '875' => ['criterion' => 3, 'note' => 'Manutenção sistemas/industrial → HM'],
        '819' => ['criterion' => 1, 'note' => 'Pró-labore diretoria → overhead adm (qty)'],
        '855' => ['criterion' => 2, 'note' => 'Treinamento pessoal → HH'],
        '24' => ['criterion' => 2, 'note' => 'Estagiários → HH'],
    ];

    return $overrides[$code] ?? null;
}

// Sort by code numerically
uksort($mapping, static fn(string $a, string $b): int => (int)$a <=> (int)$b);

$outPath = __DIR__ . '/../docs/dre_criterion_map_suggested.csv';
$fh = fopen($outPath, 'w');
if ($fh === false) {
    exit(1);
}
fputcsv($fh, ['codigo', 'descricao', 'criterio_num', 'criterio_label', 'peso_pct', 'observacao', 'linha_cf'], ';');
foreach ($mapping as $row) {
    if ($row === null) {
        continue;
    }
    fputcsv($fh, [
        $row['code'],
        $row['description'],
        $row['criterion'],
        $row['criterion_label'],
        '100',
        $row['note'],
        'R' . $row['cf_row'],
    ], ';');
}
fclose($fh);

echo "Mapeamento CUSTEIO FABRIL — " . count(array_filter($mapping)) . " contas\n\n";
printf("%-6s | %-42s | %-30s | %s\n", 'Código', 'Descrição', 'Critério sugerido', 'Obs.');
echo str_repeat('-', 115) . "\n";
foreach ($mapping as $row) {
    if ($row === null) {
        continue;
    }
    printf(
        "%-6s | %-42s | %-30s | %s\n",
        $row['code'],
        mb_substr($row['description'], 0, 42),
        $row['criterion_label'],
        mb_substr($row['note'], 0, 45)
    );
}

echo "\nCSV: $outPath\n";

// Pasta 9 summary for user
echo "\n--- Pasta 9 (folha por área → critério após distribuição) ---\n";
for ($r = 2335; $r <= 2355; $r++) {
    if (!isset($main[$r])) {
        continue;
    }
    $cells = $main[$r];
    $area = trim((string)($cells['B'] ?? ''));
    $crit = trim((string)($cells['E'] ?? ''));
    if ($area !== '' && $crit !== '') {
        echo "  $area → $crit\n";
    }
}
