<?php

declare(strict_types=1);

/**
 * Importa a documentação BEAS (database-beas.pdf) para JSON do catálogo técnico.
 *
 * Uso:
 *   php scripts/import_beas_tables_catalog.php [caminho.pdf]
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$candidates = array_values(array_filter([
    $argv[1] ?? null,
    rtrim((string) getenv('USERPROFILE'), '\\/') . '/Downloads/database-beas.pdf',
    'C:/Users/rafael.oliveira/.cursor/projects/c-wamp64-www-administrativo/attachments/c8e3c929-f320-40f2-beac-97b13b8d1bd1/database-beas.pdf',
]));

$source = null;
foreach ($candidates as $candidate) {
    if (is_string($candidate) && is_readable($candidate)) {
        $source = $candidate;
        break;
    }
}
if ($source === null) {
    fwrite(STDERR, "PDF BEAS nao encontrado.\n");
    exit(1);
}

$targetDir = dirname(__DIR__) . '/app/adms/Models/Services/AiAnalyst/Catalog/data';
$target = $targetDir . '/beas_tables.json';
if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
    fwrite(STDERR, "Nao foi possivel criar {$targetDir}\n");
    exit(1);
}

echo "Lendo {$source}...\n";
$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile($source);
$pages = $pdf->getPages();

/** @var array<string, array<string, mixed>> $byTable */
$byTable = [];

$ensure = static function (string $table) use (&$byTable): void {
    $table = strtoupper($table);
    if (!isset($byTable[$table])) {
        $byTable[$table] = [
            'id' => mb_strtolower($table),
            'title' => $table,
            'object_type' => 'table',
            'table_name' => $table,
            'module' => 'BEAS',
            'summary' => 'Tabela BEAS (Boyum/beas).',
            'sap_object_type' => '',
            'primary_key' => '',
            'key_fields' => [],
            'columns' => [],
            'related' => [],
            'synonyms' => [mb_strtolower($table)],
            'source' => 'database_beas_pdf',
        ];
    }
};

$normalizeBeasName = static function (string $raw): array {
    $raw = trim($raw);
    // PDF cola título CamelCase: BEAS_APL_HEADERRouting Header
    if (preg_match('/^(BEAS_[A-Z0-9_]*?)(?=[A-Z][a-z]|$)/u', $raw, $m) === 1) {
        $table = rtrim($m[1], '_');
        if ($table === 'BEAS' || $table === '') {
            return ['', ''];
        }

        return [strtoupper($table), trim(substr($raw, strlen($m[1])))];
    }

    return ['', ''];
};

// 1) Índice / menções "BEAS_XXX + título"
$fullText = $pdf->getText();
if (preg_match_all(
    '/(BEAS_[A-Z0-9_]+[A-Za-z0-9_]*)(?:\s*\+\s*|\s+)?([^\r\n+]{0,120})/u',
    $fullText,
    $matches,
    PREG_SET_ORDER
) > 0) {
    foreach ($matches as $match) {
        [$table, $glued] = $normalizeBeasName($match[1]);
        if ($table === '') {
            continue;
        }
        $ensure($table);
        $title = trim(preg_replace('/\s+/u', ' ', ($glued !== '' ? $glued . ' ' : '') . $match[2]) ?? '');
        $title = trim($title, " \t.+");
        if ($title !== '' && ($byTable[$table]['title'] === $table || mb_strlen($title) > mb_strlen((string) $byTable[$table]['title']))) {
            $byTable[$table]['title'] = $title;
            $byTable[$table]['summary'] = $title . ' (BEAS).';
            $byTable[$table]['synonyms'] = array_values(array_unique(array_merge(
                $byTable[$table]['synonyms'],
                [mb_strtolower($title)]
            )));
        }
    }
}

// 2) Páginas de detalhe: "BEAS_XXXTitle" + linhas Column Description
$skipColumns = [
    'column', 'description', 'item', 'accessgroup', 'printed', 'boyum', 'rights', 'reserved',
];

foreach ($pages as $pageIndex => $page) {
    $text = $page->getText();
    if ($text === '' || !str_contains($text, 'BEAS_')) {
        continue;
    }

    if (!preg_match('/(BEAS_[A-Z0-9_]*[A-Za-z0-9_]*)\s*([^\r\n]*)/u', $text, $header)) {
        continue;
    }

    [$table, $gluedTitle] = $normalizeBeasName($header[1]);
    if ($table === '') {
        continue;
    }
    $ensure($table);
    $rawTitle = trim($gluedTitle . ' ' . (string) ($header[2] ?? ''));
    $rawTitle = preg_replace('/\d+\s*\/\s*\d+.*/u', '', $rawTitle) ?? $rawTitle;
    $rawTitle = trim($rawTitle);
    if ($rawTitle !== '' && ($byTable[$table]['title'] === $table || mb_strlen($rawTitle) > 3)) {
        $byTable[$table]['title'] = $rawTitle;
        $byTable[$table]['summary'] = $rawTitle . ' (BEAS).';
    }

    $columns = [];
    $lines = preg_split('/\R/u', $text) ?: [];
    $started = false;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^Column\s+Description/i', $line) === 1) {
            $started = true;
            continue;
        }
        if (!$started) {
            // Algumas páginas não trazem o cabeçalho "Column Description".
            if (preg_match('/^([A-Z][A-Za-z0-9_]{1,60})\s+([A-Za-z].+)$/u', $line) === 1
                && preg_match('/^BEAS_/i', $line) !== 1
            ) {
                $started = true;
            } else {
                continue;
            }
        }
        if (preg_match('/^\d+\s*\/\s*\d+/', $line) === 1 || str_contains($line, 'Printed by:')) {
            break;
        }
        if (preg_match('/^BEAS_[A-Z0-9_]+/i', $line) === 1) {
            break;
        }
        if (preg_match('/^([A-Za-z][A-Za-z0-9_]{1,60})\s+(.+)$/u', $line, $col) !== 1) {
            continue;
        }
        $name = $col[1];
        if (in_array(mb_strtolower($name), $skipColumns, true)) {
            continue;
        }
        // Evita lixo de frases quebradas do PDF.
        if (preg_match('/^(This|event|will|the|and|only|for|with)$/i', $name) === 1) {
            continue;
        }
        $description = trim(preg_replace('/\s+/u', ' ', $col[2]) ?? '');
        $columns[$name] = [
            'name' => $name,
            'description' => $description,
        ];
    }

    if ($columns !== []) {
        // Não sobrescreve se já houver mais colunas capturadas.
        $existing = is_array($byTable[$table]['columns'] ?? null) ? $byTable[$table]['columns'] : [];
        if (count($columns) >= count($existing)) {
            $byTable[$table]['columns'] = array_values($columns);
        }
        $keyFields = [];
        foreach (array_keys($columns) as $name) {
            if (preg_match('/(?:^ID$|Code$|_ID$|DocEntry|ItemCode|BELNR|BELPOS)/i', $name) === 1) {
                $keyFields[] = $name;
            }
            if (count($keyFields) >= 6) {
                break;
            }
        }
        if ($keyFields !== []) {
            $byTable[$table]['key_fields'] = array_values(array_unique(array_merge(
                $byTable[$table]['key_fields'],
                $keyFields
            )));
        }
    }
}

// Sinônimos PT genéricos úteis
$ptHints = [
    'PERS' => ['pessoal', 'funcionario', 'funcionário', 'colaborador'],
    'ANWZEIT' => ['apontamento', 'presenca', 'presença', 'attendance'],
    'ABWZEIT' => ['falta', 'ausencia', 'ausência'],
    'WHS' => ['deposito', 'depósito', 'bin', 'endereco', 'endereço'],
    'APL' => ['roteiro', 'routing', 'rota'],
    'FTSTL' => ['bom', 'lista tecnica', 'lista técnica', 'estrutura'],
    'ARBZEIT' => ['tempo', 'apontamento'],
];

foreach ($byTable as $table => &$entry) {
    foreach ($ptHints as $needle => $synonyms) {
        if (str_contains($table, $needle)) {
            $entry['synonyms'] = array_values(array_unique(array_merge($entry['synonyms'], $synonyms)));
        }
    }
    $entry['synonyms'] = array_values(array_filter(
        array_unique($entry['synonyms']),
        static fn (string $value): bool => $value !== ''
    ));
    if ($entry['title'] === $table && $entry['summary'] === 'Tabela BEAS (Boyum/beas).') {
        $entry['summary'] = 'Tabela BEAS ' . $table . '.';
    }
}
unset($entry);

ksort($byTable);
$entries = array_values($byTable);
$withColumns = count(array_filter($entries, static fn (array $e): bool => ($e['columns'] ?? []) !== []));

$payload = [
    'version' => 'beas-database-pdf-2018',
    'generated_at' => gmdate('c'),
    'source_file' => basename($source),
    'page_count' => count($pages),
    'count' => count($entries),
    'tables_with_columns' => $withColumns,
    'entries' => $entries,
];

$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($json === false || file_put_contents($target, $json . PHP_EOL) === false) {
    fwrite(STDERR, "Falha ao gravar {$target}\n");
    exit(1);
}

echo 'Importadas ' . count($entries) . ' tabelas BEAS (' . $withColumns . ' com colunas) em ' . $target . PHP_EOL;
