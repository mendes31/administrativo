<?php
/**
 * Análise local de PPT/XLSX de custeio — não commitar com dados sensíveis.
 * Uso: php scripts/analyze_cost_files.php
 */

declare(strict_types=1);

function extractXmlText(string $xml): string
{
    $xml = preg_replace('/<a:tbl[\s\S]*?<\/a:tbl>/u', ' [TABELA] ', $xml) ?? $xml;
    $text = strip_tags($xml);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return trim($text);
}

function readPptxSlides(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException("Não foi possível abrir PPT: $path");
    }
    $slides = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (!preg_match('#ppt/slides/slide(\d+)\.xml$#', $name, $m)) {
            continue;
        }
        $xml = $zip->getFromName($name);
        $slides[(int)$m[1]] = extractXmlText((string)$xml);
    }
    $zip->close();
    ksort($slides);
    return $slides;
}

function colLetters(int $col): string
{
    $letters = '';
    while ($col > 0) {
        $col--;
        $letters = chr(65 + ($col % 26)) . $letters;
        $col = intdiv($col, 26);
    }
    return $letters;
}

function readXlsxSheets(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException("Não foi possível abrir XLSX: $path");
    }

    $shared = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $sxml = simplexml_load_string($sharedXml);
        if ($sxml !== false) {
            $sxml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($sxml->si as $si) {
                $parts = [];
                if (isset($si->t)) {
                    $parts[] = (string)$si->t;
                }
                foreach ($si->r as $r) {
                    $parts[] = (string)$r->t;
                }
                $shared[] = implode('', $parts);
            }
        }
    }

    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    $sheetMap = [];
    if ($workbookXml && $relsXml) {
        $wb = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);
        $relById = [];
        foreach ($rels->Relationship as $rel) {
            $relById[(string)$rel['Id']] = (string)$rel['Target'];
        }
        $wb->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        foreach ($wb->sheets->sheet as $sheet) {
            $rid = (string)$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $target = $relById[$rid] ?? '';
            $target = ltrim(str_replace('\\', '/', $target), '/');
            if (!str_starts_with($target, 'xl/')) {
                $target = 'xl/' . $target;
            }
            $sheetMap[(string)$sheet['name']] = $target;
        }
    }

    $result = [];
    foreach ($sheetMap as $sheetName => $sheetPath) {
        $xml = $zip->getFromName($sheetPath);
        if ($xml === false) {
            continue;
        }
        $sxml = simplexml_load_string($xml);
        if ($sxml === false) {
            continue;
        }
        $sxml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];
        foreach ($sxml->sheetData->row as $row) {
            $r = (int)$row['r'];
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string)$c['r'];
                preg_match('/([A-Z]+)/', $ref, $cm);
                $col = $cm[1] ?? '';
                $type = (string)$c['t'];
                $value = '';
                if ($type === 's') {
                    $idx = (int)$c->v;
                    $value = $shared[$idx] ?? '';
                } elseif (isset($c->v)) {
                    $value = (string)$c->v;
                } elseif (isset($c->is->t)) {
                    $value = (string)$c->is->t;
                }
                if ($value !== '') {
                    $cells[$col] = $value;
                }
            }
            if ($cells !== []) {
                $rows[$r] = $cells;
            }
        }
        $result[$sheetName] = $rows;
    }

    $zip->close();
    return $result;
}

function dumpSheetPreview(array $rows, int $maxRows = 80): string
{
    $out = [];
    ksort($rows);
    $count = 0;
    foreach ($rows as $rnum => $cells) {
        if ($count++ >= $maxRows) {
            $out[] = '... (truncado)';
            break;
        }
        ksort($cells);
        $parts = [];
        foreach ($cells as $col => $val) {
            $v = mb_substr(preg_replace('/\s+/u', ' ', (string)$val) ?? '', 0, 120);
            $parts[] = "$col$rnum=$v";
        }
        $out[] = implode(' | ', $parts);
    }
    return implode("\n", $out);
}

$pptPath = 'C:/Users/rafael.oliveira/Downloads/Tutorial Planilha Custeio Fabril.pptx';
$xlsxPath = 'S:/Temporaria/CONSULTORIA PRECIFICAÇÃO/Planilha de Custeio Tiaraju 202605.xlsx';
$outDir = sys_get_temp_dir() . '/inv_cost_analysis_output';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

echo "=== PPT SLIDES ===\n";
$slides = readPptxSlides($pptPath);
$pptOut = '';
foreach ($slides as $num => $text) {
    $pptOut .= "\n--- SLIDE $num ---\n$text\n";
}
file_put_contents("$outDir/ppt_text.txt", $pptOut);
echo "Slides: " . count($slides) . " -> $outDir/ppt_text.txt\n";

echo "\n=== XLSX SHEETS ===\n";
$sheets = readXlsxSheets($xlsxPath);
$xlsxOut = '';
foreach ($sheets as $name => $rows) {
    $xlsxOut .= "\n=== ABA: $name (" . count($rows) . " linhas com dados) ===\n";
    $xlsxOut .= dumpSheetPreview($rows, 100) . "\n";
}
file_put_contents("$outDir/xlsx_preview.txt", $xlsxOut);
echo "Abas: " . implode(', ', array_keys($sheets)) . " -> $outDir/xlsx_preview.txt\n";
