<?php

declare(strict_types=1);

/**
 * Gera docs/CANAL_DENUNCIAS_EVIDENCIAS_MINISTERIO_TRABALHO.docx a partir do Markdown.
 * Uso: php scripts/generate_mt_evidence_docx.php
 */

$root = dirname(__DIR__);
$mdPath = $root . '/docs/CANAL_DENUNCIAS_EVIDENCIAS_MINISTERIO_TRABALHO.md';
$outPath = $root . '/docs/CANAL_DENUNCIAS_EVIDENCIAS_MINISTERIO_TRABALHO.docx';

if (!is_file($mdPath)) {
    fwrite(STDERR, "Arquivo não encontrado: {$mdPath}\n");
    exit(1);
}

if (!class_exists(ZipArchive::class)) {
    fwrite(STDERR, "Extensão ZipArchive não disponível.\n");
    exit(1);
}

$md = file_get_contents($mdPath);
$bodyXml = markdownToWordBody($md);

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
    . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<w:body>' . $bodyXml
    . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>'
    . '<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/>'
    . '</w:sectPr></w:body></w:document>';

$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
    . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
    . '</Types>';

$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
    . '</Relationships>';

$docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '</Relationships>';

$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:style w:type="paragraph" w:styleId="Normal" w:default="1"><w:name w:val="Normal"/>'
    . '<w:rPr><w:sz w:val="22"/><w:lang w:val="pt-BR"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/>'
    . '<w:pPr><w:spacing w:before="240" w:after="120"/></w:pPr>'
    . '<w:rPr><w:b/><w:sz w:val="32"/><w:color w:val="1F3864"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/>'
    . '<w:pPr><w:spacing w:before="200" w:after="80"/></w:pPr>'
    . '<w:rPr><w:b/><w:sz w:val="26"/><w:color w:val="2E5496"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading3"><w:name w:val="heading 3"/>'
    . '<w:pPr><w:spacing w:before="160" w:after="60"/></w:pPr>'
    . '<w:rPr><w:b/><w:sz w:val="24"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Quote"><w:name w:val="Quote"/>'
    . '<w:pPr><w:ind w:left="720"/><w:spacing w:before="80" w:after="80"/>'
    . '<w:shd w:val="clear" w:color="auto" w:fill="F2F2F2"/></w:pPr>'
    . '<w:rPr><w:i/><w:sz w:val="22"/></w:rPr></w:style>'
    . '</w:styles>';

$zip = new ZipArchive();
if ($zip->open($outPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Não foi possível criar: {$outPath}\n");
    exit(1);
}

$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rels);
$zip->addFromString('word/_rels/document.xml.rels', $docRels);
$zip->addFromString('word/styles.xml', $styles);
$zip->addFromString('word/document.xml', $documentXml);
$zip->close();

echo "Gerado: {$outPath}\n";

function markdownToWordBody(string $md): string
{
    $lines = preg_split('/\r\n|\r|\n/', $md) ?: [];
    $xml = '';
    $i = 0;
    $n = count($lines);
    $inCode = false;
    $inQuote = false;
    $quoteBuffer = [];

    while ($i < $n) {
        $line = $lines[$i];

        if (str_starts_with($line, '```')) {
            if ($inCode) {
                $inCode = false;
                $xml .= paragraph('—', 'Normal');
            } else {
                if ($inQuote) {
                    $xml .= flushQuote($quoteBuffer);
                    $inQuote = false;
                    $quoteBuffer = [];
                }
                $inCode = true;
                $codeLines = [];
                $i++;
                while ($i < $n && !str_starts_with($lines[$i], '```')) {
                    $codeLines[] = $lines[$i];
                    $i++;
                }
                if ($codeLines !== []) {
                    $xml .= paragraph(implode(' → ', array_map('trim', $codeLines)), 'Quote');
                }
            }
            $i++;
            continue;
        }

        if ($inCode) {
            $i++;
            continue;
        }

        if (preg_match('/^#{1}\s+(.+)$/', $line, $m)) {
            if ($inQuote) {
                $xml .= flushQuote($quoteBuffer);
                $inQuote = false;
                $quoteBuffer = [];
            }
            $xml .= paragraph(stripInline($m[1]), 'Heading1');
            $i++;
            continue;
        }

        if (preg_match('/^#{2}\s+(.+)$/', $line, $m)) {
            if ($inQuote) {
                $xml .= flushQuote($quoteBuffer);
                $inQuote = false;
                $quoteBuffer = [];
            }
            $xml .= paragraph(stripInline($m[1]), 'Heading2');
            $i++;
            continue;
        }

        if (preg_match('/^#{3}\s+(.+)$/', $line, $m)) {
            if ($inQuote) {
                $xml .= flushQuote($quoteBuffer);
                $inQuote = false;
                $quoteBuffer = [];
            }
            $xml .= paragraph(stripInline($m[1]), 'Heading3');
            $i++;
            continue;
        }

        if (preg_match('/^\|(.+)\|\s*$/', $line) && $i + 1 < $n && preg_match('/^\|[\s\-:|]+\|\s*$/', $lines[$i + 1])) {
            if ($inQuote) {
                $xml .= flushQuote($quoteBuffer);
                $inQuote = false;
                $quoteBuffer = [];
            }
            [$tableXml, $consumed] = parseTable(array_slice($lines, $i));
            $xml .= $tableXml;
            $i += $consumed;
            continue;
        }

        if (preg_match('/^>\s?(.*)$/', $line, $m)) {
            $inQuote = true;
            $quoteBuffer[] = $m[1];
            $i++;
            continue;
        }

        if ($inQuote && trim($line) !== '') {
            $quoteBuffer[] = ltrim($line, '> ');
            $i++;
            continue;
        }

        if ($inQuote && trim($line) === '') {
            $xml .= flushQuote($quoteBuffer);
            $inQuote = false;
            $quoteBuffer = [];
            $i++;
            continue;
        }

        if (preg_match('/^-\s+(.+)$/', $line, $m)) {
            $xml .= paragraph('• ' . stripInline($m[1]), 'Normal');
            $i++;
            continue;
        }

        if (preg_match('/^(\d+)\.\s+(.+)$/', $line, $m)) {
            $xml .= paragraph($m[1] . '. ' . stripInline($m[2]), 'Normal');
            $i++;
            continue;
        }

        if (preg_match('/^-\s+\[\s?\]\s+(.+)$/', $line, $m)) {
            $xml .= paragraph('☐ ' . stripInline($m[1]), 'Normal');
            $i++;
            continue;
        }

        if (trim($line) === '---') {
            $xml .= paragraph('', 'Normal');
            $i++;
            continue;
        }

        if (trim($line) === '') {
            $i++;
            continue;
        }

        if (preg_match('/^\*(.+)\*$/', trim($line), $m)) {
            $xml .= paragraphItalic($m[1]);
            $i++;
            continue;
        }

        $xml .= paragraph(stripInline($line), 'Normal');
        $i++;
    }

    if ($inQuote && $quoteBuffer !== []) {
        $xml .= flushQuote($quoteBuffer);
    }

    return $xml;
}

/** @return array{0: string, 1: int} */
function parseTable(array $lines): array
{
    if ($lines === []) {
        return ['', 0];
    }

    $rows = [];
    $i = 0;
    foreach ($lines as $idx => $line) {
        if (!preg_match('/^\|(.+)\|\s*$/', $line)) {
            break;
        }
        if (preg_match('/^\|[\s\-:|]+\|\s*$/', $line)) {
            $i++;
            continue;
        }
        $cells = array_map('trim', explode('|', trim($line, '|')));
        $rows[] = $cells;
        $i++;
    }

    if ($rows === []) {
        return ['', 0];
    }

    $colCount = max(array_map('count', $rows));
    $tbl = '<w:tbl><w:tblPr>'
        . '<w:tblW w:w="0" w:type="auto"/>'
        . '<w:tblBorders>'
        . '<w:top w:val="single" w:sz="4" w:space="0" w:color="AAAAAA"/>'
        . '<w:left w:val="single" w:sz="4" w:space="0" w:color="AAAAAA"/>'
        . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="AAAAAA"/>'
        . '<w:right w:val="single" w:sz="4" w:space="0" w:color="AAAAAA"/>'
        . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="AAAAAA"/>'
        . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="AAAAAA"/>'
        . '</w:tblBorders></w:tblPr><w:tblGrid>';
    for ($c = 0; $c < $colCount; $c++) {
        $tbl .= '<w:gridCol w:w="2400"/>';
    }
    $tbl .= '</w:tblGrid>';

    foreach ($rows as $rIdx => $row) {
        $tbl .= '<w:tr>';
        for ($c = 0; $c < $colCount; $c++) {
            $cell = $row[$c] ?? '';
            $shd = $rIdx === 0 ? '<w:shd w:val="clear" w:color="auto" w:fill="D9E2F3"/>' : '';
            $tbl .= '<w:tc><w:tcPr>' . $shd . '</w:tcPr>';
            $style = $rIdx === 0 ? 'Heading3' : 'Normal';
            $tbl .= paragraph(stripInline($cell), $style, true);
            $tbl .= '</w:tc>';
        }
        $tbl .= '</w:tr>';
    }

    $tbl .= '</w:tbl>';
    $tbl .= paragraph('', 'Normal');

    return [$tbl, $i];
}

function flushQuote(array $lines): string
{
    $text = implode(' ', array_map('stripInline', $lines));

    return paragraph($text, 'Quote');
}

function stripInline(string $text): string
{
    $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text) ?? $text;
    $text = preg_replace('/`([^`]+)`/', '$1', $text) ?? $text;

    return trim($text);
}

function paragraph(string $text, string $style = 'Normal', bool $inTable = false): string
{
    $text = xmlEscape($text);
    if ($text === '') {
        $pPr = $inTable ? '' : '';
        return '<w:p>' . $pPr . '</w:p>';
    }

    $pPr = '<w:pPr><w:pStyle w:val="' . xmlEscape($style) . '"/></w:pPr>';
    $runs = formatRuns($text);

    return '<w:p>' . $pPr . $runs . '</w:p>';
}

function paragraphItalic(string $text): string
{
    $text = xmlEscape(stripInline($text));

    return '<w:p><w:pPr><w:pStyle w:val="Normal"/></w:pPr>'
        . '<w:r><w:rPr><w:i/></w:rPr><w:t xml:space="preserve">' . $text . '</w:t></w:r></w:p>';
}

function formatRuns(string $text): string
{
    return '<w:r><w:t xml:space="preserve">' . $text . '</w:t></w:r>';
}

function xmlEscape(string $text): string
{
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
